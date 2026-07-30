<?php

namespace Tests\Feature;

use Tests\TestCase;

class SaleControllerTest extends TestCase
{
    public function test_sales_new_customer_form_hides_email_field_for_quicker_checkout()
    {
        $viewContents = file_get_contents(resource_path('views/sales.blade.php'));

        $this->assertStringNotContainsString('id="newCustomerEmail"', $viewContents);
        $this->assertStringNotContainsString('for="newCustomerEmail"', $viewContents);
    }
}
