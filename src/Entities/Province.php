<?php

namespace Sagautam5\LocalStateNepal\Entities;

use Sagautam5\LocalStateNepal\Exceptions\LoadingException;
use Sagautam5\LocalStateNepal\Helpers\Helper;
use Sagautam5\LocalStateNepal\Loaders\ProvinceLoader;

/**
 * Class Province
 * @package Sagautam5\LocalStateNepal\Entities
 */
class Province
{
    /**
     * @var mixed|null
     */
    private $provinces;

    /**
     * @var string
     */
    private $lang;

    /**
     * Province constructor.
     * @param $lang
     * @throws \Sagautam5\LocalStateNepal\Exceptions\LoadingException
     */
    public function __construct($lang = 'en')
    {
        try{
            $this->lang = $lang;

            $loader = new ProvinceLoader($this->lang);
            $this->provinces = $loader->provinces();
        }catch (LoadingException $exception){
            throw $exception;
        }
    }

    /**
     * Get List of All Provinces
     * @return mixed|null
     */
    public function allProvinces()
    {
        return $this->provinces;
    }

    /**
     * Find Province By ID
     *
     * @param $id
     * @return false|int|string
     */
    public function find($id)
    {
        $key = (array_search($id, array_column($this->provinces, 'id')));

        return is_int($key) ? $this->provinces[$key]:null;
    }

    /**
     * Get Province With Largest Area
     * @return mixed
     */
    public function largest()
    {
        $area = array_column($this->provinces, 'area_sq_km');

        if($this->lang == 'np'){
            $area = array_map(function ($item){
                return Helper::numericEnglish($item);
            }, $area);
        }

        return $this->provinces[array_search(max($area), $area)];
    }

    /**
     * Get Province With Smallest Area
     *
     * @return mixed
     */
    public function smallest()
    {
        $area = array_column($this->provinces, 'area_sq_km');

        if($this->lang == 'np'){
            $area = array_map(function ($item){
                return Helper::numericEnglish($item);
            }, $area);
        }
        return $this->provinces[array_search(min($area), $area)];
    }


    /**
     * Get Provinces With Districts
     *
     * @return array
     * @throws LoadingException
     */
    public function getProvincesWithDistricts()
    {
        $district = new District($this->lang);

        $provinces = $this->allProvinces();

        return array_map(function ($item) use($district){
            $item = (array) $item;
            $item['districts'] = $district->getDistrictsByProvince($item['id']);
            return (object) $item;
        },$provinces);
    }

    /**
     * Get Provinces With Districts With Municipalities
     *
     * @return array
     * @throws LoadingException
     */
    public function getProvincesWithDistrictsWithMunicipalities()
    {
        $district = new District($this->lang);
        $municipality = new Municipality($this->lang);

        $provinces = $this->allProvinces();

        return array_map(function ($provinceItem) use($district, $municipality){
            $provinceItem = (array) $provinceItem;
            $provinceDistricts = $district->getDistrictsByProvince($provinceItem['id']);
            $provinceItem['districts'] = array_map(function ($districtItem) use ($municipality){
                $districtItem = (array)$districtItem;
                $municipalities = $municipality->getMunicipalitiesByDistrict($districtItem['id']);
                $districtItem['municipalities'] = array_map(function ($municipalityItem) use ($municipality){
                    $municipalityItem = (array) $municipalityItem;
                    $municipalityItem['wards'] = $municipality->wards($municipalityItem['id']);
                    return (object) $municipalityItem;
                }, $municipalities);

                return (object) $districtItem;
            }, $provinceDistricts);
            return (object) $provinceItem;
        },$provinces);
    }
}