<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
<h2>МОБИКА: настройка подключения к онлайн-кассе</h2>
<form method="post" action="options.php">
    <?php settings_fields( 'kasscloud-settings' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Методы платежа</th>
        <td><select name="kasscloud_payment_gateway[]" multiple>
            <?php foreach (WC()->payment_gateways->get_available_payment_gateways() as $k => $v) {
                if (!$v->is_available()) continue;
            ?>
            <option value="<?php echo esc_attr($k) ?>"<?php if ($this->checkPaymentMethod($k)) echo ' selected'; ?>><?php echo esc_html($v->get_method_title()) ?></option>
            <?php } ?>
        </select></td>
        </tr>

        <tr valign="top">
        <th scope="row">Формировать чек прихода предоплаты (после оплаты покупалем на сайте) при статусе заказа:</th>
        <td><select name="kasscloud_incoming_order_status">
            <?php foreach (wc_get_order_statuses() as $k => $v) {
                    $k = str_replace('wc-', '', $k);
            ?>
            <option value="<?php echo esc_attr($k) ?>"<?php if ($k == get_option('kasscloud_incoming_order_status')) echo ' selected'; ?>><?php echo esc_html($v) ?></option>
            <?php } ?>
        </select></td>
        </tr>

        <tr valign="top">
        <th scope="row">Формировать чек полного расчета (после отгрузки товара/выполнения услуги) при статусе заказа:</th> 
        <td><select name="kasscloud_final_order_status">
            <?php foreach (wc_get_order_statuses() as $k => $v) {
                    $k = str_replace('wc-', '', $k);
            ?>
            <option value="<?php echo esc_attr($k) ?>"<?php if ($k == get_option('kasscloud_final_order_status')) echo ' selected'; ?>><?php echo esc_html($v) ?></option>
            <?php } ?>
        </select></td>
        </tr>

        <tr valign="top">
        <th scope="row">Чеки возврата</th>
        <td><input type="checkbox" name="kasscloud_refunds" value="1"<?php if (get_option('kasscloud_refunds')) echo ' checked'; ?> /></td>
        </tr>

        <tr valign="top">
        <th scope="row">Система налогообложения</th>
        <td><select name="kasscloud_tax_system">
            <?php foreach ($this->taxSystems() as $k => $v) { ?>
            <option value="<?php echo $k ?>"<?php if ($k == get_option('kasscloud_tax_system')) echo ' selected'; ?>><?php echo $v ?></option>
            <?php } ?>
        </select></td>
        </tr>

        <tr valign="top">
        <th scope="row">НДС на товары</th>
        <td><select name="kasscloud_product_vat">
            <?php foreach ($this->vatList() as $k => $v) { ?>
            <option value="<?php echo $k ?>"<?php if ($k == get_option('kasscloud_product_vat')) echo ' selected'; ?>><?php echo $v ?></option>
            <?php } ?>
        </select></td>
        </tr>

        <tr valign="top">
        <th scope="row">НДС на доставку</th>
        <td><select name="kasscloud_delivery_vat">
            <?php foreach ($this->vatList() as $k => $v) { ?>
            <option value="<?php echo $k ?>"<?php if ($k == get_option('kasscloud_delivery_vat')) echo ' selected'; ?>><?php echo $v ?></option>
            <?php } ?>
        </select></td>
        </tr>

        <tr valign="top">
        <th scope="row">Адрес сервера</th>
        <td><input type="text" name="kasscloud_api_url" value="<?php echo get_option('kasscloud_api_url'); ?>" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">Группа устройств</th>
        <td><input type="text" name="kasscloud_group_id" value="<?php echo get_option('kasscloud_group_id'); ?>" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">Ключ API</th>
        <td><input type="password" name="kasscloud_group_key" value="<?php echo get_option('kasscloud_group_key'); ?>" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">Время ожидания ответа, сек.</th>
        <td><input type="text" name="kasscloud_timeout" value="<?php echo get_option('kasscloud_timeout'); ?>" /></td>
        </tr>
    </table>
    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>
</form>
</div>
