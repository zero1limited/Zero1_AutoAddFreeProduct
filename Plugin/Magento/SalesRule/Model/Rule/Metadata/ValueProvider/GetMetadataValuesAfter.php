<?php
namespace Zero1\AutoAddFreeProduct\Plugin\Magento\SalesRule\Model\Rule\Metadata\ValueProvider;

use Zero1\AutoAddFreeProduct\Model\RulesApplierPlugin;

class GetMetadataValuesAfter
{
    /**
     * @param $valueProvider \Magento\SalesRule\Model\Rule\Metadata\ValueProvider\Interceptor
     * @param $metaDataValues
     */
    public function afterGetMetadataValues($valueProvider, $metaDataValues)
    {
        if(isset(
            $metaDataValues['actions'],
            $metaDataValues['actions']['children'],
            $metaDataValues['actions']['children']['simple_action'],
            $metaDataValues['actions']['children']['simple_action']['arguments'],
            $metaDataValues['actions']['children']['simple_action']['arguments']['data'],
            $metaDataValues['actions']['children']['simple_action']['arguments']['data']['config'],
            $metaDataValues['actions']['children']['simple_action']['arguments']['data']['config']['options']
        )){
            $metaDataValues['actions']['children']['simple_action']['arguments']['data']['config']['options'][] = [
                'label' => __('Auto Add Free Product'),
                'value' => RulesApplierPlugin::AUTO_ADD_FREE_PRODUCT,
            ];
        }

        return $metaDataValues;
    }
}