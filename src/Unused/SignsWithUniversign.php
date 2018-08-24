<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Utilities\Universign;


trait SignsWithUniversign
{
    protected $docs = [];
    protected $signers = [];

    protected function initSign($docs, $signers, $returnUrl, $certType = 'simple', $chainingMode = 'web', $hand = 0)
    {
        // -----------------------------------------------
        //  Universign request
        // -----------------------------------------------
        $universign = new Universign();
        $r = $universign->prepareToSign($docs, $signers, $returnUrl, $certType, $chainingMode, $hand);

        if($r['status'] === 'success'){

            // -----------------------------------------------
            //  Universign ready
            // -----------------------------------------------
            $result = $r['data']->value();
            return [
                'id' => $result->structMem('id')->scalarVal(),
                'url' => $result->structMem('url')->scalarVal(),
            ];

        }else{

            // -----------------------------------------------
            //  Universign a refusé la requête
            // -----------------------------------------------
            abort(500, 'Universign signature failed. Status : '.$r['status'].' | Code : '.$r['code'].' | Message : '.$r['message']);
        }
    }

    protected function getTransactionInfo($uni_id)
    {
        $universign = new Universign();
        $r = $universign->getTransactionInfo($uni_id);
        if($r['status'] === 'success')
        {
            $value = $r['data']->value();
            return $value;
        }
        else
        {
            abort(500, 'Universign transaction info failed. Status : '.$r['status'].' | Code : '.$r['code'].' | Message : '.$r['message']);
        }
    }

    protected function getSignedDocuments($uni_id)
    {
        $universign = new Universign();
        $r = $universign->getDocuments($uni_id);
        if($r['status'] == 'success')
        {
            $result = $r['data']->value();
            $files = [];

            for($i = 0; $i < $result->arraySize(); $i++)
            {
                $files[] = [
                    'name' => $result->arrayMem($i)->structMem('name')->scalarVal(),
                    'content' => $result->arrayMem($i)->structMem('content')->scalarVal(),
                ];
            }
            return $files;
        }
        else
        {
            abort(500, 'Universign documents retreive failed. Status : '.$r['status'].' | Code : '.$r['code'].' | Message : '.$r['message']);
        }
    }

    public function getSignatureStatus($uni_id)
    {
        $info = $this->getTransactionInfo($uni_id);

        $result = [
            'status' => $info->structMem('status')->scalarVal(),
            'currentSignerIdx' => $info->structMem('currentSigner')->scalarVal(),
            'signers' => []
        ];

        $signerInfos = $info->structMem('signerInfos')->scalarVal();
        foreach($signerInfos as $signerInfo){
            $signer = [
                'status' => $signerInfo->structMem('status')->scalarVal(),
                'firstname' => $signerInfo->structMem('firstName')->scalarVal(),
                'lastname' => $signerInfo->structMem('lastName')->scalarVal(),
                'actionDate' => $signerInfo['actionDate'] ? $signerInfo->structMem('actionDate')->scalarVal() : '',
            ];
            $result['signers'][] = $signer;
        }
        return $result;
    }
}
