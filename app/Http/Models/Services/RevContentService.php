<?php

/**
 * Created by PhpStorm.
 * User: ilan
 * Date: 29/07/16
 * Time: 01:58
 */
namespace App\Http\Models\Services;

use App\Http\Models\Interfaces\RevContentInterface;
use Maatwebsite\Excel\Facades\Excel;


/**
 * @property mixed data
 * @property mixed accessToken
 */
class RevContentService implements RevContentInterface
{
    public $method = 'GET';
    public $headers = ['Content-type:application/x-www-form-urlencoded', 'Cache-Control: no-cache'];
    public $url = 'https://api.revcontent.io/';
    public $token;


    public function getAccessTokens()
    {
        $url = $this->url . 'oauth/token';
        $accessTokenParams = 'grant_type=client_credentials&client_id=shacharr&client_secret=0dab321cba582fae6ce7fe06d13724108833a6e8';

        try {
            $this->accessToken = $this->curlExecution($url, 'POST', $this->headers, $accessTokenParams);

        } catch (\Exception $e) {
            dd($e->getMessage());
            abort(503, $e->getMessage());
        }

        if (!empty($this->accessToken)) {
            $this->accessToken = $this->accessToken = json_decode($this->accessToken);
        }

        return $this->accessToken;
    }

    /**
     * @param string $timeFrom
     * @param string $timeTo
     * @return mixed
     * @internal param string $time
     * @internal param string $time2
     */
    public function getAllContent($timeFrom = '-2 days', $timeTo = '-1 hour')
    {
        $this->checkToken($this->method);
        $params = ['date_from' => date('Y-m-d', strtotime($timeFrom)), 'date_to' => date('Y-m-d', strtotime($timeTo))];
        $url = $this->url . 'stats/api/v1.0/boosts/content?' . http_build_query($params);
        $this->data = $this->curlExecution($url, $this->method, $this->headers);
        return $this->data;
    }

    /**
     * @return mixed
     */
    public function getAllBoots()
    {
        $this->checkToken($this->method);
        $params = null;
        $this->url .= 'stats/api/v1.0/boosts/';

        $this->data = $this->curlExecution($this->url, $this->method, $this->headers);
        return $this->data;
    }

    /**
     * @return mixed
     */
    public function getAllCountries()
    {
        $this->checkToken($this->method);
        $params = null;
        $this->url = 'stats/api/v1.0/countries';
        $this->countries = $this->curlExecution($this->url, $this->method, $this->headers);
        dd(json_decode($this->countries));
        return $this->countries;
    }

    /**
     * @param string $params
     */
    public function getAllWidget($params = '')
    {
        $this->widget = null;
        $this->checkToken($this->method);
        $this->url .= 'stats/api/v1.0/widgets';
        $params = $params != "" ? '?' . http_build_query($params) : '';

        if ($params != '')
            $this->url .= '?' . $params;

        $this->widget = $this->curlExecution($this->url, $this->method, $this->headers);
    }


    /**
     * @param string $params
     * @return mixed
     */
    public function getWidgetStates($params = '')
    {
        $this->checkToken($this->method);
        $this->url = 'stats/api/v1.0/boosts/:boost_id/widgets/stats';

        $params = $params != "" ? '?' . http_build_query($params) : '';
        if ($params != '')
            $this->url .= '?' . $params;
        $this->data = $this->curlExecution($this->url, $this->method, $this->headers);
        return $this->data;
    }

    public function checkToken()
    {
        if (empty($this->token)) {
            $this->updateHeaders($this->method);
        }
        return;
    }

    /**
     * @param $method
     * @return string
     */
    public function updateHeaders($method)
    {
        if ($method == 'GET') {

            $this->token = $this->accessToken->access_token;
            $this->headers = [
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json',
                'Cache-Control: no-cache'
            ];
            $data = 'updated';
        }
        return $data;
    }

    /**
     * @param $content
     * @return array
     */
    public function arrangeContent($content)
    {
        $contentNew = [];

        if (is_string($content) && $content != '') {
            $dataObject = json_decode($content);
            foreach ($dataObject->data as $key => $boosts) {
                $contentNew[$key]['date'] = date('Y-m-d');

                foreach ($boosts as $prop => $val) {

//                   /*pass thus keys/
                    if ($prop == 'image_url' ||
                        $prop == 'enabled' ||
                        $prop == 'admin_status' ||
                        $prop == 'avg_cpc' ||
                        $prop == 'content_type'
                    ) {
                        continue;
                    }

                    if ($prop == 'target_url') {
                        $temp = parse_url($val);
                        $contentNew[$key]['host'] = $temp['host'];
                        $contentNew[$key]['query'] = str_replace('&amp;', " and ", $temp['query']);
                        continue;
                    }

                    $contentNew[$key][$prop] = $val;
                    /*$newVal = str_replace("?", "", strstr($val, '?'));
                    $newVal = str_replace(';', "\n", $newVal);*/
                }
            }
            return $contentNew;
        }
    }

    /*CURL INTEGRATION*/
    public function curlExecution($url, $method, $headers = null, $params = null)
    {
        $data = null;
        $data = curl_init($url);
        curl_setopt($data, CURLOPT_HTTPHEADER, $headers);

        if ($method == 'POST') {
            curl_setopt($data, CURLOPT_POST, true);
            curl_setopt($data, CURLOPT_POSTFIELDS, $params);
        }
        curl_setopt($data, CURLOPT_RETURNTRANSFER, true);
        $result = (curl_exec($data));
        return $result;
        return json_decode($result);
    }

    /*OUTPUT FILES */
    public function saveToCSVFile($data)
    {
        ini_set('max_execution_time', 300);
        $data = (json_encode(json_decode($data)->data));
        $parser = Parser::create(new Logger('json-parser'));
        $file = 'ilan.csv';
        file_put_contents($file, $data);
        $file = file_get_contents($file);
        $json = json_decode($file);
        $parser->process($json);
        $results = $parser->getCsvFiles();
        dd($results);
    }

    /**
     * @param $contentToParse
     */
    public function saveToExcelFile($contentToParse)
    {
        $this->cTp = $contentToParse;
        Excel::create('excel doc', function ($excel) {
            $excel->sheet('summery', function ($sheet) {
                $head = [];
                foreach ($this->cTp as $key => $val) {
                    foreach ($val as $k => $v) {
                        if ($key == 1) {
                            $head[] = $k;
                        }
                        $cont[$key][] = $v;
                    }
                }

                array_unshift($cont, $head);

                $sheet->fromArray($cont);
                $sheet->setAllBorders('thin');
                $sheet->setAutoSize(true);
                $sheet->setfitToHeight(true);
                $sheet->setStyle(array(
                    'font' => array(
                        'name' => 'Calibri',
                        'text-align' => 'center'
                    ),
                ));
                $sheet->setHeight(array(
                    2 => 30,
                ));
            });
        })->download('xls');

    }

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    private function __toString()
    {
        return '';
    }


}
