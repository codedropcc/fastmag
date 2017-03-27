<?php

namespace Fastmag\Product;

use Fastmag\Exception;
use Fastmag\ArrayHelper;
use Fastmag\QB;

use Fastmag\Product\ProductAbstract;
use Fastmag\Product\Factory;

/**
 * @Injectable(scope="prototype")
 */
class Bundle extends ProductAbstract
{
    const DEFAULT_BUNDLE_OPTION_VALUE_STORE_ID_ADMIN = 0;

    protected function customOptionsSave() {
        //here bundle->save stuff started.
        if ($this->data['options'] && $this->data['selections']) {
            $this->_saveBundleOptions();
        }
        else {
            if ($this->isNew())
                throw new Exception('No have bundle options and selections data.');
        }
        
        return $this;
    }

    private function _saveBundleOptions() {
        $optionIds = [];
        $optionKeys = [
            'parent_id', 'required', 'position', 'type'
        ];
        foreach ($this->data['options'] as $key => $option) {
            $bundleOptionData = [];
            foreach ($option as $k => $v) {
                if (in_array($k, $optionKeys))
                    $bundleOptionData[$k] = $v;
            }
            $bundleOptionData['parent_id'] = $this->id;
            
            $optionIds[$key] = QB::table('catalog_product_bundle_option')
                ->insert($bundleOptionData);

            $bundleOptionValueData = [
                'option_id' => $optionIds[$key],
                'store_id' => isset($option['store_id']) ? $option['store_id'] : self::DEFAULT_BUNDLE_OPTION_VALUE_STORE_ID_ADMIN,
                'title' => $option['title'],
            ];
            
            QB::table('catalog_product_bundle_option_value')
                ->insert($bundleOptionValueData);
        }
        $this->_saveBundleOptionSelection($optionIds);

        return $this;
    }

    private function _saveBundleOptionSelection($optionsIds) {
        if (count($optionsIds) != count($this->data['selections']))
            throw new Exception('Count of selections and options is wrong.');
        foreach ($this->data['selections'] as $key => $selections) {
            $optionId = $optionsIds[$key];
            if (!isset($optionId))
                throw new Exception('Do not have option_id for save selection.');

            ArrayHelper::walk(function ($selection) use ($optionId) {
                $selection['option_id'] = $optionId;
                $selection['parent_product_id'] = $this->id;
                QB::table('catalog_product_bundle_selection')
                    ->insert($selection);
            }, $selections);
        }
        return $this;
    }

    public function getBundleItems() {
        return ArrayHelper::map(function ($item) {
            return Factory::create($item->product_id);
        }, QB::table('catalog_product_bundle_selection')
            ->select('product_id')
            ->where('parent_product_id', $this->id)
            ->get()
        );
    }

    public function getSelections() {
        return ArrayHelper::map(function ($item) {
            return (array)$item;
        }, QB::table('catalog_product_bundle_selection')
            ->select('*')
            ->where('parent_product_id', $this->id)
            ->get()
        );
    }
}
