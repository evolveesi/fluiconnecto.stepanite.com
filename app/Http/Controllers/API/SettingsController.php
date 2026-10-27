<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Settings;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    private $tinNumber;
    private $branchCode;
    private $lastRequestDate;
    private $baseUrl;
    public function __construct()
    {
        $settings = Settings::find(1);
        $this->baseUrl = $settings->baseUrl;
        $this->tinNumber = $settings->tinNumber;
        $this->branchCode = $settings->branchCode;
        $this->lastRequestDate = $settings->lastRequestDate;

    }

    /**
     * @return mixed
     */
    public function getTinNumber()
    {
        return $this->tinNumber;
    }

    /**
     * @param mixed $tinNumber
     */
    public function setTinNumber($tinNumber): void
    {
        $this->tinNumber = $tinNumber;
    }

    /**
     * @return mixed
     */
    public function getBranchCode()
    {
        return $this->branchCode;
    }

    /**
     * @param mixed $branchCode
     */
    public function setBranchCode($branchCode): void
    {
        $this->branchCode = $branchCode;
    }

    /**
     * @return mixed
     */
    public function getLastRequestDate()
    {
        return $this->lastRequestDate;
    }

    /**
     * @param mixed $lastRequestDate
     */
    public function setLastRequestDate($lastRequestDate): void
    {
        $this->lastRequestDate = $lastRequestDate;
    }

    /**
     * @return mixed
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @param mixed $baseUrl
     */
    public function setBaseUrl($baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }

}
