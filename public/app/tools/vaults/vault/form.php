<h4 style='margin-top:50px;'><?php print _("Enter Vault password"); ?></h4><hr>

<form method="post">
<table id="userModSelf" class="table table-condensed">

<!-- real name -->
<tr>
    <td>
        <input type="text" class="form-control input-sm" name="vaultpass">
    </td>
    <td class="submit">
        <input type="submit" class="btn btn-sm btn-success pull-right" value="<?php print _('Decrypt'); ?>">
    </td>
</tr>
<tr>
    <td class="info2"><?php print _('Please enter vault password to decrypt vault.'); ?></td>
</tr>


</table>
</form>