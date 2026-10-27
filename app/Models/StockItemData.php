<?php


namespace App\Models;


class StockItemData
{
    private $itemSeq;
    private $itemCd;
    private $itemClsCd;
    private $itemNm;
    private $bcd='';
    private $pkgUnitCd;
    private $pkg;
    private $qtyUnitCd;
    private $qty;
    private $itemExprDt='';
    private $prc;
    private $splyAmt;
    private $totDcAmt;
    private $iplCatCd = 'IPL1';
    private $tlCatCd = 'TL';
    private $vatCatCd = 'A';
    private $exciseTxCatC = 'ECM';
    private $taxblAmt;
    private $taxAmt=0.00;
    private $iplAmt=0.00;
    private $tlAmt=0.00;
    private $exciseTxAmt=0.00;
    private $totAmt=0.00;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $this->$key = $value;
            }
        }
    }

    /**
     * @return mixed
     */
    public function getItemSeq()
    {
        return $this->itemSeq;
    }

    /**
     * @param mixed $itemSeq
     */
    public function setItemSeq($itemSeq): void
    {
        $this->itemSeq = $itemSeq;
    }

    /**
     * @return mixed
     */
    public function getItemCd()
    {
        return $this->itemCd;
    }

    /**
     * @param mixed $itemCd
     */
    public function setItemCd($itemCd): void
    {
        $this->itemCd = $itemCd;
    }

    /**
     * @return mixed
     */
    public function getItemClsCd()
    {
        return $this->itemClsCd;
    }

    /**
     * @param mixed $itemClsCd
     */
    public function setItemClsCd($itemClsCd): void
    {
        $this->itemClsCd = $itemClsCd;
    }

    /**
     * @return mixed
     */
    public function getItemNm()
    {
        return $this->itemNm;
    }

    /**
     * @param mixed $itemNm
     */
    public function setItemNm($itemNm): void
    {
        $this->itemNm = $itemNm;
    }

    /**
     * @return mixed
     */
    public function getBcd()
    {
        return $this->bcd;
    }

    /**
     * @param mixed $bcd
     */
    public function setBcd($bcd): void
    {
        $this->bcd = $bcd;
    }

    /**
     * @return mixed
     */
    public function getPkgUnitCd()
    {
        return $this->pkgUnitCd;
    }

    /**
     * @param mixed $pkgUnitCd
     */
    public function setPkgUnitCd($pkgUnitCd): void
    {
        $this->pkgUnitCd = $pkgUnitCd;
    }

    /**
     * @return mixed
     */
    public function getPkg()
    {
        return $this->pkg;
    }

    /**
     * @param mixed $pkg
     */
    public function setPkg($pkg): void
    {
        $this->pkg = $pkg;
    }

    /**
     * @return mixed
     */
    public function getQtyUnitCd()
    {
        return $this->qtyUnitCd;
    }

    /**
     * @param mixed $qtyUnitCd
     */
    public function setQtyUnitCd($qtyUnitCd): void
    {
        $this->qtyUnitCd = $qtyUnitCd;
    }

    /**
     * @return mixed
     */
    public function getQty()
    {
        return $this->qty;
    }

    /**
     * @param mixed $qty
     */
    public function setQty($qty): void
    {
        $this->qty = $qty;
    }

    /**
     * @return mixed
     */
    public function getItemExprDt()
    {
        return $this->itemExprDt;
    }

    /**
     * @param mixed $itemExprDt
     */
    public function setItemExprDt($itemExprDt)
    {
        $this->itemExprDt = $itemExprDt;
    }

    /**
     * @return mixed
     */
    public function getPrc()
    {
        return $this->prc;
    }

    /**
     * @param mixed $prc
     */
    public function setPrc($prc): void
    {
        $this->prc = $prc;
    }

    /**
     * @return mixed
     */
    public function getSplyAmt()
    {
        return $this->splyAmt;
    }

    /**
     * @param mixed $splyAmt
     */
    public function setSplyAmt($splyAmt): void
    {
        $this->splyAmt = $splyAmt;
    }

    /**
     * @return mixed
     */
    public function getTotDcAmt()
    {
        return $this->totDcAmt;
    }

    /**
     * @param mixed $totDcAmt
     */
    public function setTotDcAmt($totDcAmt): void
    {
        $this->totDcAmt = $totDcAmt;
    }

    /**
     * @return string
     */
    public function getIplCatCd(): string
    {
        return $this->iplCatCd;
    }

    /**
     * @param string $iplCatCd
     */
    public function setIplCatCd( $iplCatCd): void
    {
        $this->iplCatCd = $iplCatCd;
    }

    /**
     * @return string
     */
    public function getTlCatCd()
    {
        return $this->tlCatCd;
    }

    /**
     * @param string $tlCatCd
     */
    public function setTlCatCd( $tlCatCd): void
    {
        $this->tlCatCd = $tlCatCd;
    }

    /**
     * @return string
     */
    public function getExciseTxCatC()
    {
        return $this->exciseTxCatC;
    }

    /**
     * @param string $exciseTxCatC
     */
    public function setExciseTxCatC( $exciseTxCatC)
    {
        $this->exciseTxCatC = $exciseTxCatC;
    }

    /**
     * @return mixed
     */
    public function getTaxblAmt()
    {
        return $this->taxblAmt;
    }

    /**
     * @param mixed $taxblAmt
     */
    public function setTaxblAmt($taxblAmt): void
    {
        $this->taxblAmt = $taxblAmt;
    }

    /**
     * @return mixed
     */
    public function getVatCatCd()
    {
        return $this->vatCatCd;
    }

    /**
     * @param mixed $vatCatCd
     */
    public function setVatCatCd($vatCatCd): void
    {
        $this->vatCatCd = $vatCatCd;
    }

    /**
     * @return mixed
     */
    public function getTaxAmt()
    {
        return $this->taxAmt;
    }

    /**
     * @param mixed $taxAmt
     */
    public function setTaxAmt($taxAmt): void
    {
        $this->taxAmt = $taxAmt;
    }

    /**
     * @return mixed
     */
    public function getIplAmt()
    {
        return $this->iplAmt;
    }

    /**
     * @param mixed $iplAmt
     */
    public function setIplAmt($iplAmt): void
    {
        $this->iplAmt = $iplAmt;
    }

    /**
     * @return mixed
     */
    public function getTlAmt()
    {
        return $this->tlAmt;
    }

    /**
     * @param mixed $tlAmt
     */
    public function setTlAmt($tlAmt): void
    {
        $this->tlAmt = $tlAmt;
    }

    /**
     * @return mixed
     */
    public function getExciseTxAmt()
    {
        return $this->exciseTxAmt;
    }

    /**
     * @param mixed $exciseTxAmt
     */
    public function setExciseTxAmt($exciseTxAmt): void
    {
        $this->exciseTxAmt = $exciseTxAmt;
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

    

    public function getStockItem(){
        return [
                "itemSeq"=> $this->getItemSeq(),
                "itemCd"=> $this->getItemCd(),
                "itemClsCd"=> $this->getItemClsCd(),
                "itemNm"=> $this->getItemNm(),
                "bcd"=> $this->getBcd(),
                "pkg"=> $this->getPkg(),
                "pkgUnitCd"=> $this->getPkgUnitCd(),
                "qtyUnitCd"=> $this->getQtyUnitCd(),
                "qty"=> $this->getQty(),
                "itemExprDt"=> $this->getItemExprDt(),
                "totDcAmt"=> $this->getTotDcAmt(),
                "iplCatCd"=> $this->getIplCatCd(),
                "tlCatCd"=> $this->getTlCatCd(),
                "exciseTxCatC"=> $this->getExciseTxCatC(),
                "prc"=> $this->getPrc(),
                "splyAmt"=> $this->getSplyAmt(),
                "iplAmt"=> $this->getIplAmt(),
                "tlAmt"=> $this->getTlAmt(),
                "exciseTxAmt"=> $this->getExciseTxAmt(),
                "taxblAmt"=> $this->getTaxAmt(),
                "vatCatCd"=> $this->getVatCatCd(),
                "taxAmt"=> $this->getTaxAmt(),
                "totAmt"=> $this->getTotAmt()
            ];
    }

}
