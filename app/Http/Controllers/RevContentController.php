<?php
/**
 * Created by PhpStorm.
 * User: ilan
 * Date: 28/07/16
 * Time: 22:15
 */

namespace App\Http\Controllers;

use App\Http\Models\Services\RevContentService;
use Illuminate\Routing\Controller;


/**
 * @property mixed content
 * @property mixed isContentArranged
 * @property mixed accessToken
 * @property void file
 */
class RevContentController extends Controller
{
    protected $token;
    protected $revContent;
    protected $accessTokenInfo;


    /**
     * @param RevContentService $revContentService
     */
    public function index(RevContentService $revContentService)
    {

        $this->revContent = $revContentService;
        $this->accessToken = $this->revContent->getAccessTokens();

        if (is_object($this->accessToken) && !empty($this->accessToken)) {
            $this->content = $this->revContent->getAllContent('-20 days','-18 days');

            if (!empty($this->content)) {
                $this->isContentArranged = $this->revContent->arrangeContent($this->content);

                if (!empty($this->isContentArranged)) {
                    $this->revContent->saveToExcelFile($this->isContentArranged);
//                  $this->file = $this->revContent->setToExcel($this->getAllContent());
                }
            }
        }
    }
}

