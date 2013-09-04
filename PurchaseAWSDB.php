<?php

/**
 * Description of PurchaseAWSDB
 *
 * @author gambit
 */
class PurchaseAWSDB
{
    static public function saveAuthorize($id, $username, $amount)
    {
        $row = DBBridge::getInstance()
                ->getConnection("news", "write")
                ->prepare("INSERT INTO aws_authorizes (id, username, amount, status) VALUES(?,?,?,'Pending')")
                ->bindparam("ssi", $id, $username, (int)$amount)
                ->execute()
                ->rowsAffected();
        return true;
    }
    
    static public function updateAuthorize($id, $username, $token, $status)
    {
        $row = DBBridge::getInstance()
                ->getConnection("news", "write")
                ->prepare("UPDATE aws_authorizes SET token = ?, status = ?, updated = NOW() WHERE username = ? AND id = ?")
                ->bindparam("ssss", $token, $status, $username, $id)
                ->execute()
                ->rowsAffected();
        
        if ($row==1){
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Return WEB purchase info by purchaseName
     * @param type $name
     * @return type
     */
    static public function webPurchaseInfo($name)
    {
        $row = DBBridge::getInstance()
                ->getConnection("news", "read")
                ->prepare("SELECT name, slots, sync, coast, title, iconurl FROM purchaseinfo WHERE platform = 'Web' AND name = ?")
                ->bindparam("s", $name)
                ->execute()
                ->fetch();
        return $row;
    }
    
    /**
     * Return WEB purchase list
     * @param type $name
     * @return type
     */
    static public function webPurchaseList()
    {
        $rows = DBBridge::getInstance()
                ->getConnection("news", "read")
                ->prepare("SELECT name, coast, title, iconurl FROM purchaseinfo WHERE platform = 'Web'")
                ->execute()
                ->fetch_all();
        return $rows;
    }
    
    /**
     * Return SUCCESS AWS purchase info by purchase id;
     * @abstract Use for refund transaction
     * @param type $id
     * @return type
     */
    static public function getSuccessAWSPurchaseInfo($username, $id)
    {
        $row = DBBridge::getInstance()
                ->getConnection("news", "read")
                ->prepare("
                    SELECT aws_payments.sender, aws_payments.transactionId, aws_payments.token 
                    FROM aws_payments, aws_authorizes
                    WHERE aws_payments.token = aws_authorizes.token
                    AND aws_authorizes.username = ?
                    AND aws_payments.status = 'SUCCESS'
                    AND aws_payments.refunded = '0' 
                    AND aws_payments.id = ?")
                ->bindparam("ss", $username, $id)
                ->execute()
                ->fetch();
        return $row;
    }
    
    /**
     * Return user purchases.
     * @param type $username
     * @return type
     */
    static public function getAWSPurchaseList($username)
    {
        $rows = DBBridge::getInstance()
                ->getConnection("news", "read")
                ->prepare("
                    SELECT 
                    aws_payments.id, 
                    aws_payments.purchasename,
                    aws_payments.coast,
                    aws_payments.status
                    FROM aws_payments, aws_authorizes
                    WHERE aws_payments.token = aws_authorizes.token
                    AND aws_payments.status = 'SUCCESS'
                    AND aws_payments.refunded = '0'
                    AND aws_authorizes.username = ?")
                ->bindparam("s", $username)
                ->execute()
                ->fetch_all();
        return $rows;
    }
    
    static public function savePurchase($id, $reference, $token, $pname, $coast, $transaction, $status)
    {
        $row = DBBridge::getInstance()
                ->getConnection("news", "write")
                ->prepare("INSERT INTO aws_payments (id, token, purchasename, coast, transactionId, reference, status) VALUES(?,?,?,?,?,?,?)")
                ->bindparam("sssssss", $id, $token, $pname, $coast, $transaction, $reference, $status)
                ->execute()
                ->rowsAffected();
        return true;
    }
    
    static public function updatePurchase($reference, $transaction, $sender, $status)
    {
        $row = DBBridge::getInstance()
                ->getConnection("news", "write")
                ->prepare("UPDATE aws_payments SET sender = ?, status = ?, updated = NOW() WHERE reference = ? AND transactionId = ?")
                ->bindparam("ssss", $sender, $status, $reference, $transaction)
                ->execute()
                ->rowsAffected();
        
        if ($row==1){
            return true;
        } else {
            return false;
        }
    }
    
    static public function refundPurchase($transaction)
    {
        $row = DBBridge::getInstance()
                ->getConnection("news", "write")
                ->prepare("UPDATE aws_payments SET refunded = 1, updated = NOW() WHERE transactionId = ?")
                ->bindparam("s", $transaction)
                ->execute()
                ->rowsAffected();
        
        if ($row==1){
            return true;
        } else {
            return false;
        }
    }
    
    static public function saveRefund($id, $username, $transaction, $status)
    {
        $row = DBBridge::getInstance()
                ->getConnection("news", "write")
                ->prepare("INSERT INTO aws_refunds (id, username, transactionId, status) VALUES(?,?,?,?)")
                ->bindparam("ssss", $id, $username, $transaction, $status)
                ->execute()
                ->rowsAffected();
        return true;
    }
    
    static public function updateRefund($id, $transaction, $status)
    {
        $row = DBBridge::getInstance()
                ->getConnection("news", "write")
                ->prepare("UPDATE aws_refunds SET status = ?, updated = NOW() WHERE id = ? AND transactionId = ?")
                ->bindparam("ssss", $sender, $status, $id, $transaction)
                ->execute()
                ->rowsAffected();
        
        if ($row==1){
            return true;
        } else {
            return false;
        }
    }
}