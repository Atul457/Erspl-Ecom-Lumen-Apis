<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Home;

class HomeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Home::create([
            'gst' => 18.0,
            'count2' => 8,
            'count1' => 8,
            'version' => 3,
            'title' => 'eRSPL',
            'logo' => 'logo.png',
            'sms_vendor' => "ZAP",
            'tInfo_temp' => "bhan",
            'contact' => "18002570369",
            'title1' => "Top Products",
            'zap_key' => env("ZAP_KEY"),
            'fav_icon' => "Logo-01.png",
            'title2' => 'Household Care',
            'gallery1' => "bannerNewE.jpg",
            'gallery2' => "bannerNewD.jpg",
            'paytm_mid' => env("PAYTM_MID"),
            'email' => 'e-rspl@rsplgroup.com',
            'paytm_mkey' => env("PAYTM_MKEY"),
            'address' => "RSPL Limited, Plot No 124, Sec 44, Gurgaon",
            'category2' => 5,
            'shop_range' => 8,
            'category1_id' => 5,
            'category2_id' => 5,
            'shop_capping' => 10,
            'force_status' => 1,
            'wallet_status' => 0,
            'minimum_value' => 1,
            'packing_charge' => 0,
            'referral_amount' => 60,
            'meta_keyword' => "Kirana Store",
            'fortius_key' => env("FORTIUS_KEY"),
            'company_name' => "Kirana Store Pvt. Ltd.",
            'delivery_type' => 1,
            'prepaid_status' => 1,
            'order_capping' => 10,
            'delivery_charge' => 100,
            'weight_capping' => 25000,
            'meta_description' => "Kirana Store",
            'notification_key' => env("NOTIFICATION_KEY"),
            'footer_description' => "Vishwas Mart in Jaipur is one of the leading businesses in the General Stores. Also known for General Stores, Departmental Stores, Supermarkets, Provision Stores, Bakery Product Retailers, Grocery Home Delivery Services, Milk Home Delivery Services, Bakery Food Home Delivery and much more. Find Address, Contact Number, Reviews & Ratings, Photos, Maps of Vishwas Mart, Jaipur.",
        ]);
    }
}
