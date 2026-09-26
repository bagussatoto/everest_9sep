<?php
// START OF COMPLETE REPEATED LOGIC
class MdlCheckoutStock extends CI_Model
{
    public function verify_and_lock_invoice_stock($item_id, $requested_qty)
    {
        $item_id = (int) $item_id;
        $requested_qty = (float) $requested_qty;

        if ($item_id <= 0) {
            return array(
                "success" => false,
                "message" => "Item tidak valid.",
                "item_id" => $item_id,
            );
        }

        if ($requested_qty <= 0) {
            return array(
                "success" => false,
                "message" => "Jumlah permintaan harus lebih besar dari nol.",
                "item_id" => $item_id,
                "requested_qty" => $requested_qty,
            );
        }

        $this->db->trans_start();

        $stock_sql = "
            SELECT
                id,
                item_id,
                available_qty,
                locked_qty,
                status,
                trash
            FROM invoice_stock
            WHERE item_id = ?
              AND trash = 0
            ORDER BY id ASC
            LIMIT 1
            FOR UPDATE
        ";
        $stock_row = $this->db->query($stock_sql, array($item_id))->row_array();

        if (!isset($stock_row['id'])) {
            $this->db->trans_complete();
            return array(
                "success" => false,
                "message" => "Item stok invoice tidak ditemukan.",
                "item_id" => $item_id,
            );
        }

        $available_qty = isset($stock_row['available_qty']) ? (float) $stock_row['available_qty'] : 0;
        $locked_qty = isset($stock_row['locked_qty']) ? (float) $stock_row['locked_qty'] : 0;

        if ($available_qty < $requested_qty) {
            $this->db->trans_complete();
            return array(
                "success" => false,
                "message" => "Stok tidak mencukupi untuk checkout.",
                "item_id" => $item_id,
                "requested_qty" => $requested_qty,
                "available_qty" => $available_qty,
            );
        }

        $verify_sql = "
            INSERT INTO invoice_stock_checkout_log (
                item_id,
                requested_qty,
                available_qty,
                locked_qty_before,
                locked_qty_after,
                status,
                created_at
            )
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ";

        $locked_qty_after = $locked_qty + $requested_qty;

        $this->db->query($verify_sql, array(
            $item_id,
            $requested_qty,
            $available_qty,
            $locked_qty,
            $locked_qty_after,
            "verified",
        ));

        $update_sql = "
            UPDATE invoice_stock
            SET available_qty = available_qty - ?,
                locked_qty = locked_qty + ?,
                updated_at = NOW()
            WHERE id = ?
              AND available_qty >= ?
        ";

        $update_result = $this->db->query($update_sql, array(
            $requested_qty,
            $requested_qty,
            $stock_row['id'],
            $requested_qty,
        ));

        if ($this->db->affected_rows() != 1) {
            $this->db->trans_complete();
            return array(
                "success" => false,
                "message" => "Gagal mengunci stok saat checkout.",
                "item_id" => $item_id,
                "requested_qty" => $requested_qty,
            );
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return array(
                "success" => false,
                "message" => "Transaksi stock verification gagal.",
                "item_id" => $item_id,
                "requested_qty" => $requested_qty,
            );
        }

        return array(
            "success" => true,
            "message" => "Stok berhasil diverifikasi dan dikunci untuk checkout.",
            "item_id" => $item_id,
            "requested_qty" => $requested_qty,
            "available_qty_after" => $available_qty - $requested_qty,
            "locked_qty_after" => $locked_qty_after,
        );
    }
}
// END OF COMPLETE REPEATED LOGIC