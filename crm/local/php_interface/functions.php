<?php

function checkCrmStoreDeal()
{
    global $APPLICATION;
    if (strpos($APPLICATION->GetCurPage(false), '/crm/deal/edit/0/') !== false) {
        ?>
        <script>
            window.onload = function () {
                var storeCheckbox = document.getElementById('ID_DO_USE_EXTERNAL_SALE');

                if (storeCheckbox) {
                    storeCheckbox.click();
                }
            }
        </script>
        <?
    }
}
