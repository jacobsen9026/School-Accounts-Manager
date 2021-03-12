<?php
/*
 * The MIT License
 *
 * Copyright 2020 cjacobsen.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

$domain = $this->domain;

use App\Models\View\PermissionMapPrinter;
use System\Lang;

?>
<h4 class="mb-3">
    Manage Privilege Levels

    <div class="text-secondary clickable far fa-question-circle fa-xs" data-toggle="collapse"
         data-target="#privilegeLevelCollapse"></div>

</h4>

<div class="collapse" id="privilegeLevelCollapse">
    <div class="row p-2">
        <div class="col-sm pb-2 mb-3">
            <?= Lang::getHelp('Privilege_Levels') ?>

        </div>
        <div class="col-sm pb-2 mb-3">
            <?= Lang::getHelp('Permissions') ?>

        </div>
    </div>

    <div class="row pb-2 mb-3">
        <div class="col-sm">
            <?= Lang::getHelp('Super_Admin') ?>

        </div>
    </div>
</div>
<div class="row">
    <div class="col">
        <?php echo PermissionMapPrinter::printPrivilegeLevels($domain->getId()); ?>
    </div>
</div>
<div class="row">
    <div class="col" id="managePrivilegeLevelsContainer">
        <?php
        echo PermissionMapPrinter::printAddPrivilegeLevelForm($domain->getId());
        ?>
    </div>

</div>

