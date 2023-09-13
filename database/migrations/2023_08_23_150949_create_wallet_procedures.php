<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateWalletProcedures extends Migration
{
    public function up()
    {
        DB::unprepared('
        CREATE PROCEDURE check_balance(IN userId INT, IN orderTotal DECIMAL(10, 2))
        BEGIN
            DECLARE userId_ INT;
            DECLARE userNotFound BOOLEAN;
            DECLARE walletBalance DECIMAL(10, 2);
            DECLARE requiredBalance DECIMAL(10, 2);
            DECLARE isInsuffecientBalance BOOLEAN;
        
            SELECT COALESCE(wallet_balance, 0), id INTO walletBalance, userId_ FROM tbl_registration WHERE id = userId;
        
            IF userId_ IS NULL THEN
                SET userNotFound = TRUE;
                SET walletBalance = 0.00;
                SET requiredBalance = 0.00;
                SET isInsuffecientBalance = FALSE;
            ELSE
                SET userNotFound = FALSE;

                IF walletBalance IS NULL THEN
                    SET walletBalance = 0;
                END IF;

                SET requiredBalance = orderTotal - walletBalance;
                    
                IF requiredBalance > 0 THEN
                    SET isInsuffecientBalance = TRUE;
                ELSE
                    SET isInsuffecientBalance = FALSE;
                END IF;

            END IF;
        
            SELECT isInsuffecientBalance AS is_insufficient_balance, requiredBalance AS required_balance, walletBalance AS wallet_balance, userNotFound AS user_not_found;
        END
        ');
    }

    public function down()
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS check_balance');
    }
}