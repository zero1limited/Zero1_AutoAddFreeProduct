<?php
namespace Zero1\AutoAddFreeProduct\Observer\SalesRule;

use Magento\Framework\Event\ObserverInterface;

class ActionsTabPrepareFormObserver implements ObserverInterface
{
    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        // This no longer works, as of 2.1.2 #M2
//        /** @var \Magento\Framework\Data\Form $form */
//        $form = $observer->getForm();
//
//        $field = $form->getElement('simple_action');
//        $options = $field->getValues();
//
//        $options[] = array(
//            'value' => 'auto_add_free_product',
//            'label' => 'Auto Add Free Product'
//        );
//
//        $field->setValues($options);
        // skjhkjhr kwejhrwkejrhkwejrh kwjehrwekjrhwekjrhwekrj h
    }
}
