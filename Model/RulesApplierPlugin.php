<?php
namespace Zero1\AutoAddFreeProduct\Model;

use Magento\Quote\Model\Quote\Address;
use Magento\SalesRule\Model\RulesApplier;

/**
 * Class RulesApplier
 * @package Magento\SalesRule\Model\RulesApplierPlugin
 */
class RulesApplierPlugin
{
    const AUTO_ADD_FREE_PRODUCT = 'auto_add_free_product';

    /**
     * @var \Magento\SalesRule\Model\Utility
     */
    protected $validatorUtility;

    /**
     * @param \Magento\SalesRule\Model\Utility $utility
     */
    public function __construct(
        \Magento\SalesRule\Model\Utility $utility
    ) {
        $this->validatorUtility = $utility;
    }

    /**
     * @param RulesApplier $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param \Magento\SalesRule\Model\ResourceModel\Rule\Collection $rules
     * @param bool $skipValidation
     * @param mixed $couponCode
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundApplyRules(
        RulesApplier $subject,
        \Closure $proceed,
        $item,
        $rules,
        $skipValidation,
        $couponCode
    ) {
        // Find, process and remove the auto add free product rule from the stack
        $originalRules = array();
        foreach ($rules as $rule) {
            $this->removeFreeProductDiscount($rule, $item);
            if ($rule->getSimpleAction() === self::AUTO_ADD_FREE_PRODUCT) {
                if (!$this->validatorUtility->canProcessRule($rule, $item->getAddress())) {
                    // The free product rule is no longer valid, remove the free products
                    $this->removeFreeProductDiscount($rule, $item);
                } else {
                    // Force it to apply the rule, otherwise the actions are never met and
                    // it would never get applied.
                    $this->addFreeProduct($rule, $item);
                }
            } else {
                $originalRules[] = $rule;
            }
        }

        return $proceed($item, $originalRules, $skipValidation, $couponCode);
    }

    public function removeFreeProductDiscount($rule, $item)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $product = $objectManager->create('Magento\Catalog\Model\Product');

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $item->getQuote();
        $ruleActions = $rule->getActions()->asArray();
        foreach ($ruleActions['conditions'] as $action) {
            $freeProductId = $product->getIdBySku($action['value']);
            if ($freeProductId) {
                // The product exists, reset the custom price
                foreach ($quote->getAllItems() as $item) {
                    if ($item->getProductId() == $freeProductId) {
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

    public function addFreeProduct($rule, $item)
    {
        file_put_contents('/home/magento/htdocs/var/log/rules.log', __METHOD__.PHP_EOL, FILE_APPEND);
        /** @var \Magento\Catalog\Model\Product $product */
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $product = $objectManager->create('Magento\Catalog\Model\Product');

        ////////////////////////////////////////////////////////////////////////////////////
        // Check the actions...
        ////////////////////////////////////////////////////////////////////////////////////
        $actions = $rule->getActions()->asArray();
        if (!$actions) {
            return; // Invalid rule
        }

        if (!isset($actions['type']) || $actions['type'] !== 'Magento\SalesRule\Model\Rule\Condition\Product\Combine') {
            return; // Invalid rule type
        }

        if (!isset($actions['conditions']) && count($actions['conditions']) !== 1) {
            return; // Invalid rule condition
        }

        // Is this a product combination rule?
        $condition = $actions['conditions'][0];
        if ($condition['type'] !== 'Magento\SalesRule\Model\Rule\Condition\Product') {
            return;
        }
        $freeItemId = $product->getIdBySku($condition['value']);
        file_put_contents('/home/magento/htdocs/var/log/rules.log', 'freeItemId'.PHP_EOL, FILE_APPEND);
        file_put_contents('/home/magento/htdocs/var/log/rules.log', $freeItemId.PHP_EOL, FILE_APPEND);
        ////////////////////////////////////////////////////////////////////////////////////
        // Check the conditions...
        ////////////////////////////////////////////////////////////////////////////////////
        $conditions = $rule->getConditions()->asArray();
        if (!$conditions) {
            return; // Invalid rule
        }

        if (!isset($conditions['type']) || $conditions['type'] !== 'Magento\SalesRule\Model\Rule\Condition\Combine') {
            return; // Invalid rule type
        }

        if (!isset($conditions['conditions']) && count($conditions['conditions']) !== 1) {
            return; // Invalid rule condition
        }

        // Is this a product combination rule?
        $condition = $conditions['conditions'][0];
        if ($condition['type'] !== 'Magento\SalesRule\Model\Rule\Condition\Product\Found') {
            return; // Invalid rule condition
        }

        if (!isset($condition['conditions']) && count($condition['conditions']) !== 1) {
            return; // Invalid rule condition
        }

        // Is this a product combination rule?
        $condition = $condition['conditions'][0];
        if ($condition['type'] !== 'Magento\SalesRule\Model\Rule\Condition\Product') {
            return; // Invalid rule condition
        }
        /* disabling, the action is the
        if ($condition['attribute'] !== 'sku') {
            file_put_contents('/home/magento/htdocs/var/log/rules.log', 'Invalid rule condition4'.PHP_EOL, FILE_APPEND);
            file_put_contents('/home/magento/htdocs/var/log/rules.log', $condition['attribute'].PHP_EOL, FILE_APPEND);
            return; // Invalid rule condition
        }
        */
        $paidItemId = $product->getIdBySku($condition['value']);

        ////////////////////////////////////////////////////////////////////////////////////
        // Get the number of paid/free items in the cart
        ////////////////////////////////////////////////////////////////////////////////////
        file_put_contents('/home/magento/htdocs/var/log/rules.log', 'Get the number of paid/free items in the cart'.PHP_EOL, FILE_APPEND);

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $item->getQuote();

        $paidItemQty = 0;
        foreach ($quote->getAllItems() as $item) {
            if ($item->getProductId() == $paidItemId) {
                $paidItemQty = $item->getQty();
                break;
            }
        }

        ////////////////////////////////////////////////////////////////////////////////////
        // Add/Remove free items in the cart
        ////////////////////////////////////////////////////////////////////////////////////
        // Find the existing quote item
        $quoteItem = null;
        foreach ($quote->getAllItems() as $item) {
            if ($item->getProductId() == $freeItemId) {
                $quoteItem = $item;
                break;
            }
        }

        if (!$quoteItem) {
            $freeProduct = $objectManager->create('Magento\Catalog\Model\Product');
            $freeProduct = $freeProduct->load($freeItemId);
            $quoteItem = $quote->addProduct($freeProduct, $paidItemQty);
        }

        if ($quoteItem) {
            $quoteItem->setCustomPrice(0);
            $quoteItem->setOriginalCustomPrice(0);
            $quoteItem->setQty($paidItemQty);
            $quoteItem->save();
        }
    }
}
