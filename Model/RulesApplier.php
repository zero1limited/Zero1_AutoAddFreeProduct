<?php
namespace Zero1\AutoAddFreeProduct\Model;

use Magento\Quote\Model\Quote\Address;

/**
 * Class RulesApplier
 * @package Magento\SalesRule\Model\Validator
 */
class RulesApplier extends \Magento\SalesRule\Model\RulesApplier
{
    /**
     * Apply rules to current order item
     *
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param \Magento\SalesRule\Model\ResourceModel\Rule\Collection $rules
     * @param bool $skipValidation
     * @param mixed $couponCode
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function applyRules($item, $rules, $skipValidation, $couponCode)
    {
        $address = $item->getAddress();
        $appliedRuleIds = [];
        /* @var $rule \Magento\SalesRule\Model\Rule */
        foreach ($rules as $rule) {
            if (!$this->validatorUtility->canProcessRule($rule, $address)) {
                continue;
            }

            //$this->salesrule_validator_process_cant_process_rule($rule);

            if (!$skipValidation && !$rule->getActions()->validate($item)) {
                $childItems = $item->getChildren();
                $isContinue = true;
                if (!empty($childItems)) {
                    foreach ($childItems as $childItem) {
                        if ($rule->getActions()->validate($childItem)) {
                            $isContinue = false;
                        }
                    }
                }
                if ($isContinue) {
                    continue;
                }
            }

            $this->applyRule($item, $rule, $address, $couponCode);
            $appliedRuleIds[$rule->getRuleId()] = $rule->getRuleId();

            //$this->salesrule_validator_process();

            if ($rule->getStopRulesProcessing()) {
                break;
            }
        }

        return $appliedRuleIds;
    }

    public function salesrule_validator_process_cant_process_rule($rule)
    {
        $ruleActions = $rule->getActions()->asArray();
        foreach($ruleActions['conditions'] as $action) {
            /* @var $helper Mage_Catalog_Helper_Product */
            $product = Mage::helper('catalog/product')->getProduct(
                $action['value'],
                $quote->getStoreId(),
                'sku'
            );

            if ($product->getId()) {
                // The product exists, reset the custom price
                foreach($quote->getAllItems() as $item) {
                    if ($item->getProductId() == $product->getId()) {
                        $item->setCustomPrice(NULL);
                        $item->setOriginalCustomPrice(NULL);
                        $item->setBaseRowTotal(0);
                        $item->setRowTotal(0);
                        $item->save();
                        break;
                    }
                }
            }
        }
    }

    public function salesrule_validator_process()
    {
        /* @var $rule Mage_SalesRule_Model_Rule */
        if($rule->getSimpleAction() != self::AUTO_FREE_GIFT){
            return;
        }

        $ruleActions = $rule->getActions()->asArray();
        foreach($ruleActions['conditions'] as $action){
            /* @var $autoAddedProduct Mage_Catalog_Model_Product */
            $autoAddedProduct = Mage::getModel('catalog/product');
            $autoAddedProduct->setStoreId($quote->getStoreId());
            $autoAddedProduct->load($autoAddedProduct->getIdBySku($action['value']));

            if (!$autoAddedProduct->getId()){
                Mage::logException(new Exception('Could not find the free product to add, SKU was "'.$action['value'].'"'));
                continue;
            }

            $this->addProductToQuote($quote, $autoAddedProduct, $rule);
        }
    }
}
