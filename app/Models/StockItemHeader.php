<?php


namespace App\Models;


use App\Settings;

class StockItemHeader
{
    private $sarNo;
    private $orgSarNo;
    private $regTyCd;
    private $custTpin;
    private $custNm;
    private $custBhfId;
    private $sarTyCd;
    private $ocrnDt;
    private $totItemCnt;
    private $totTaxblAmt;
    private $totTaxAmt;
    private $totAmt;
    private $remark;
    private $regrId = 'Admin';
    private $regrNm = 'Admin';
    private $modrNm = 'Admin';
    private $modrId = 'Admin';
    private $StockItemList = [];


    public function __construct()
    {
        // Set default values for optional fields
        $this->custTpin = $this->custNm = $this->custBhfId = $this->remark = null;
    }

    /**
     * @return mixed
     */
    public function getSarNo()
    {
        return $this->sarNo;
    }

    /**
     * @param mixed $sarNo
     */
    public function setSarNo($sarNo): void
    {
        $this->sarNo = $sarNo;
    }

    /**
     * @return mixed
     */
    public function getOrgSarNo()
    {
        return $this->orgSarNo;
    }

    /**
     * @param mixed $orgSarNo
     */
    public function setOrgSarNo($orgSarNo): void
    {
        $this->orgSarNo = $orgSarNo;
    }

    /**
     * @return mixed
     */
    public function getRegTyCd()
    {
        return $this->regTyCd;
    }

    /**
     * @param mixed $regTyCd
     */
    public function setRegTyCd($regTyCd): void
    {
        $this->regTyCd = $regTyCd;
    }

    /**
     * @return null
     */
    public function getCustTpin()
    {
        return $this->custTpin;
    }

    /**
     * @param null $custTpin
     */
    public function setCustTpin($custTpin): void
    {
        $this->custTpin = $custTpin;
    }

    /**
     * @return null
     */
    public function getCustNm()
    {
        return $this->custNm;
    }

    /**
     * @param null $custNm
     */
    public function setCustNm($custNm): void
    {
        $this->custNm = $custNm;
    }

    /**
     * @return null
     */
    public function getCustBhfId()
    {
        return $this->custBhfId;
    }

    /**
     * @param null $custBhfId
     */
    public function setCustBhfId($custBhfId): void
    {
        $this->custBhfId = $custBhfId;
    }

    /**
     * @return mixed
     */
    public function getSarTyCd()
    {
        return $this->sarTyCd;
    }

    /**
     * @param mixed $sarTyCd
     */
    public function setSarTyCd($sarTyCd): void
    {
        $this->sarTyCd = $sarTyCd;
    }

    /**
     * @return mixed
     */
    public function getOcrnDt()
    {
        return $this->ocrnDt;
    }

    /**
     * @param mixed $ocrnDt
     */
    public function setOcrnDt($ocrnDt): void
    {
        $this->ocrnDt = $ocrnDt;
    }

    /**
     * @return mixed
     */
    public function getTotItemCnt()
    {
        return $this->totItemCnt;
    }

    /**
     * @param mixed $totItemCnt
     */
    public function setTotItemCnt($totItemCnt): void
    {
        $this->totItemCnt = $totItemCnt;
    }

    /**
     * @return mixed
     */
    public function getTotTaxblAmt()
    {
        return $this->totTaxblAmt;
    }

    /**
     * @param mixed $totTaxblAmt
     */
    public function setTotTaxblAmt($totTaxblAmt): void
    {
        $this->totTaxblAmt = $totTaxblAmt;
    }

    /**
     * @return mixed
     */
    public function getTotTaxAmt()
    {
        return $this->totTaxAmt;
    }

    /**
     * @param mixed $totTaxAmt
     */
    public function setTotTaxAmt($totTaxAmt): void
    {
        $this->totTaxAmt = $totTaxAmt;
    }

    /**
     * @return mixed
     */
    public function getTotAmt()
    {
        return $this->totAmt;
    }

    /**
     * @param mixed $totAmt
     */
    public function setTotAmt($totAmt): void
    {
        $this->totAmt = $totAmt;
    }

    /**
     * @return null
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * @param null $remark
     */
    public function setRemark($remark): void
    {
        $this->remark = $remark;
    }

    /**
     * @return string
     */
    public function getRegrId(): string
    {
        return $this->regrId;
    }

    /**
     * @param string $regrId
     */
    public function setRegrId(string $regrId): void
    {
        $this->regrId = $regrId;
    }

    /**
     * @return string
     */
    public function getRegrNm(): string
    {
        return $this->regrNm;
    }

    /**
     * @param string $regrNm
     */
    public function setRegrNm(string $regrNm): void
    {
        $this->regrNm = $regrNm;
    }

    /**
     * @return string
     */
    public function getModrNm(): string
    {
        return $this->modrNm;
    }

    /**
     * @param string $modrNm
     */
    public function setModrNm(string $modrNm): void
    {
        $this->modrNm = $modrNm;
    }

    /**
     * @return string
     */
    public function getModrId(): string
    {
        return $this->modrId;
    }

    /**
     * @param string $modrId
     */
    public function setModrId(string $modrId): void
    {
        $this->modrId = $modrId;
    }

    /**
     * @return array
     */
    public function getStockItemList(): array
    {
        $this->StockItemList = array_map(function($item) {
            return array_filter($item);
        }, $this->StockItemList);
        return $this->StockItemList;
    }

    /**
     * @param array $StockItemList
     */
    public function setStockItemList(array $StockItemList): void
    {
        $this->StockItemList = $StockItemList;
    }

    public function retrieveStockItem(){
        $settings = Settings::find(1);
        $tinNumber = $settings->tinNumber;
        $branchCode= $settings->branchCode;
        return [
            "tpin"=> $tinNumber,
            "bhfId"=> $branchCode,
            "sarNo"=> $this->getSarNo(),
            "orgSarNo"=> $this->getOrgSarNo(),
            "regTyCd"=> $this->getRegTyCd(),
            "custTpin"=> $this->getCustTpin(),
            "custNm"=> $this->getCustNm(),
            "custBhfId"=> $this->getCustBhfId(),
            "sarTyCd"=> $this->getSarTyCd(),
            "ocrnDt"=> $this->getOcrnDt(),
            "totItemCnt"=> $this->getTotItemCnt(),
            "totTaxblAmt"=> $this->getTotTaxblAmt(),
            "totTaxAmt"=> $this->getTotAmt(),
            "totAmt"=> $this->getTotAmt(),
            "remark"=> $this->getRemark(),
            "regrId"=> $this->getRegrId(),
            "regrNm"=> $this->getRegrNm(),
            "modrNm"=> $this->getModrNm(),
            "modrId"=> $this->getModrId(),
            "itemList"=> array_values($this->getStockItemList())
        ];
    }
}
