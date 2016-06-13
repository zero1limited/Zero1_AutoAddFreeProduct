<?php
namespace Zero1\AutoAddFreeProduct\Model;

use Magento\Quote\Model\Quote\Address;

class Rule extends \Magento\SalesRule\Model\Rule
{
    /**
     * Validate rule conditions to determine if rule can run
     *
     * @param \Magento\Framework\DataObject $object
     * @return bool
     */
    public function validate(\Magento\Framework\DataObject $object)
    {
        return $this->getConditions()->validate($object);
    }
}
