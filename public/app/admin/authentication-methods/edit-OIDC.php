<?php

use Firebase\JWT\JWT;

/**
 * Set OpenID Connect method
 *****************/

// verify that user is logged in
$User->check_user_session();
// admin check
$User->is_admin();

// validate action
$Admin->validate_action();

// ID must be numeric */
if ($POST->action != 'add') {
    if (! is_numeric($POST->id)) {
        $Result->die('danger', _('Invalid ID'), true);
    }

    // fetch method settings
    $method_settings = $Admin->fetch_object('usersAuthMethod', 'id', $POST->id);
    $method_settings->params = new Params(db_json_decode($method_settings->params, true));
} else {
    $method_settings = new Params;
    $method_settings->description = 'OpenID Connect';
    // set default values
    $method_settings->params = new Params;
    $method_settings->params->token_auth_method = 'client_secret_basic';
    $method_settings->params->scopes = OIDC::DEFAULT_SCOPES;
    $method_settings->params->username_claim = 'preferred_username';
    $method_settings->params->name_claim = 'name';
    $method_settings->params->email_claim = 'email';
    $method_settings->params->groups_claim = 'groups';
}

$oidc_params = $method_settings->params;

// set delete flag
$is_disabled = $POST->action == 'delete' ? 'disabled' : '';
?>

<script>
$(document).ready(function() {
        /* bootstrap switch */
        var switch_options = {
            onColor: 'default',
            offColor: 'default',
            size: "mini"
        };
        $(".input-switch").bootstrapSwitch(switch_options);
});
</script>
<!-- header -->
<div class="pHeader"><?php print _('OpenID Connect settings').$User->print_doc_link('Authentication/OIDC.md'); ?></div>

<!-- content -->
<div class="pContent">

    <form id="editAuthMethod" name="editAuthMethod">
    <table class="editAuthMethod table table-noborder table-condensed">

    <!-- description -->
    <tr>
        <td><?php print _('Description'); ?></td>
        <td>
            <input type="text" name="description" class="form-control input-sm" value="<?php print @$method_settings->description; ?>" <?php print $is_disabled; ?> >
            <input type="hidden" name="type" value="OIDC">
            <input type="hidden" name="id" value="<?php print @$method_settings->id; ?>">
            <input type="hidden" name="action" value="<?php print escape_input($POST->action); ?>">
            <input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
        </td>
        <td class="base_dn info2">
            <?php print _('Set name for authentication method, shown on the login button'); ?>
        </td>
    </tr>

    <tr>
        <td colspan="3"><hr></td>
    </tr>

    <!-- redirect URI -->
    <tr>
        <td><?php print _('Redirect URI'); ?></td>
        <td>
            <input type="text" class="form-control input-sm" value="<?php print escape_input($User->createURL().create_link('oidc')); ?>" readonly>
        </td>
        <td class="base_dn info2">
            <?php print _('Register this redirect URI with the identity provider'); ?>
        </td>
    </tr>

    <!-- issuer -->
    <tr>
        <td><?php print _('Issuer'); ?></td>
        <td>
            <input type="text" name="issuer" class="form-control input-sm" value="<?php print escape_input($oidc_params->issuer); ?>" placeholder="https://idp.example.com/realms/example" <?php print $is_disabled; ?> >
        </td>
        <td class="base_dn info2">
            <?php print _('Issuer URL, /.well-known/openid-configuration is appended for discovery'); ?>
        </td>
    </tr>

    <!-- client ID -->
    <tr>
        <td><?php print _('Client ID'); ?></td>
        <td>
            <input type="text" name="client_id" class="form-control input-sm" value="<?php print escape_input($oidc_params->client_id); ?>" <?php print $is_disabled; ?> >
        </td>
        <td class="base_dn info2">
            <?php print _('Client ID registered with the identity provider'); ?>
        </td>
    </tr>

    <!-- client secret -->
    <tr>
        <td><?php print _('Client secret'); ?></td>
        <td>
            <input type="password" name="client_secret" class="form-control input-sm" value="" autocomplete="new-password" placeholder="<?php print is_blank($oidc_params->client_secret) ? '' : _('Unchanged'); ?>" <?php print $is_disabled; ?> >
        </td>
        <td class="base_dn info2">
            <?php print is_blank($oidc_params->client_secret) ? _('Client secret') : _('Leave blank to keep the current secret'); ?>
        </td>
    </tr>

    <!-- token endpoint authentication -->
    <tr>
        <td><?php print _('Client authentication'); ?></td>
        <td>
            <select name="token_auth_method" class="form-control input-sm input-w-auto" <?php print $is_disabled; ?>>
                <?php
                foreach (['client_secret_basic' => _('HTTP Basic (client_secret_basic)'), 'client_secret_post' => _('Form post (client_secret_post)')] as $value => $label) {
                    $selected = $oidc_params->token_auth_method == $value ? 'selected' : '';
                    print "<option value='$value' $selected>$label</option>";
                }
?>
            </select>
        </td>
        <td class="base_dn info2">
            <?php print _('How the client secret is sent to the token endpoint'); ?>
        </td>
    </tr>

    <!-- scopes -->
    <tr>
        <td><?php print _('Scopes'); ?></td>
        <td>
            <input type="text" name="scopes" class="form-control input-sm" value="<?php print escape_input($oidc_params->scopes); ?>" <?php print $is_disabled; ?> >
        </td>
        <td class="base_dn info2">
            <?php print _('Space separated, openid is always requested'); ?>
        </td>
    </tr>

    <tr>
        <td colspan="3"><hr></td>
    </tr>

    <!-- username claim -->
    <tr>
        <td><?php print _('Username claim'); ?></td>
        <td>
            <input type="text" name="username_claim" class="form-control input-sm" value="<?php print escape_input($oidc_params->username_claim); ?>" <?php print $is_disabled; ?> >
        </td>
        <td class="base_dn info2">
            <?php print _('Claim matched against phpIPAM usernames, e.g. preferred_username'); ?>
        </td>
    </tr>

    <!-- userinfo -->
    <tr>
        <td><?php print _('Use userinfo endpoint'); ?></td>
        <td>
            <input type="checkbox" class="input-switch" value="1" name="userinfo" <?php print filter_var($oidc_params->userinfo, FILTER_VALIDATE_BOOLEAN) ? 'checked' : ''; ?> <?php print $is_disabled; ?> >
        </td>
        <td class="info2">
            <?php print _('Also read claims from the userinfo endpoint'); ?>
        </td>
    </tr>

    <tr>
        <td colspan="3"><hr></td>
    </tr>

    <!-- auto-redirect -->
    <tr>
        <td><?php print _('Auto-redirect'); ?></td>
        <td>
            <input type="checkbox" class="input-switch" value="1" name="auto_redirect" <?php print filter_var($oidc_params->auto_redirect, FILTER_VALIDATE_BOOLEAN) ? 'checked' : ''; ?> <?php print $is_disabled; ?> >
        </td>
        <td class="info2">
            <?php
            $local_login = create_link('login');
print _('Send users from the login page to the identity provider').'<br>'._('Local login').': '.escape_input($local_login.(strpos($local_login, '?') === false ? '?' : '&').'local=1');
?>
        </td>
    </tr>

    <tr>
        <td colspan="3"><hr></td>
    </tr>

    <!-- JIT -->
    <tr>
        <td><?php print _('Enable JIT'); ?></td>
        <td>
            <input type="checkbox" class="input-switch" value="1" name="jit" <?php print filter_var($oidc_params->jit, FILTER_VALIDATE_BOOLEAN) ? 'checked' : ''; ?> <?php print $is_disabled; ?> >
        </td>
        <td class="info2">
            <?php print _('Create and update users from claims at every login'); ?>
        </td>
    </tr>

    <?php
    $jit_claims = [
        'name_claim' => [_('Name claim'), _('Real name, default name')],
        'email_claim' => [_('Email claim'), _('Email address, default email')],
        'groups_claim' => [_('Groups claim'), _('Group names matched to phpIPAM groups').'<br>'._('blank=manage groups in phpIPAM')],
        'admin_group' => [_('Administrator group'), _('Members of this group are Administrators').'<br>'._('blank=manage role in phpIPAM')],
        'modules_claim' => [_('Modules claim'), _('Module permissions, e.g. *:1,vlan:3').'<br>'._('blank=manage modules in phpIPAM')],
    ];
foreach ($jit_claims as $name => [$label, $help]) { ?>
    <tr>
        <td><?php print $label; ?></td>
        <td>
            <input type="text" name="<?php print $name; ?>" class="form-control input-sm" value="<?php print escape_input($oidc_params->{$name}); ?>" <?php print $is_disabled; ?> >
        </td>
        <td class="base_dn info2">
            <?php print $help; ?>
        </td>
    </tr>
    <?php } ?>

    <tr>
        <td colspan="3"><hr></td>
    </tr>

    <!-- debug -->
    <tr>
        <td><?php print _('Debugging'); ?></td>
        <td>
            <input type="checkbox" class="input-switch" value="1" name="debug" <?php print filter_var($oidc_params->debug, FILTER_VALIDATE_BOOLEAN) ? 'checked' : ''; ?> <?php print $is_disabled; ?> >
        </td>
        <td class="info2">
            <?php print _('Show error details on the login page').' ('._('not for production use').')'; ?>
        </td>
    </tr>

    </table>
    </form>

    <?php
    $warnings = [];
if ($User->settings->prettyLinks != 'Yes') {
    $warnings[] = _('Prettify links is disabled. The redirect URI changes if it is enabled later.');
}

if (strcasecmp((string) Config::ValueOf('cookie_samesite', 'Lax'), 'Strict') === 0) {
    $warnings[] = _('$cookie_samesite = "Strict" in config.php prevents OIDC logins, use "Lax".');
}

if (Config::ValueOf('offline_mode')) {
    $warnings[] = _('Offline mode is enabled. OIDC still connects to the configured identity provider.');
}

if (! class_exists(JWT::class)) {
    $warnings[] = _('Composer package firebase/php-jwt is missing, run composer install.');
}

$error = php_feature_missing(['curl', 'openssl', 'json']);
if (is_string($error)) {
    $warnings[] = $error;
}

if ($POST->action != 'delete') {
    foreach ($warnings as $warning) {
        $Result->warn('warning', $warning);
    }
}
?>
</div>


<!-- footer -->
<div class="pFooter">
    <div class="btn-group">
        <button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
        <button class='btn btn-sm btn-default submit_popup <?php print $POST->action == 'delete' ? 'btn-danger' : 'btn-success'; ?>' data-script="app/admin/authentication-methods/edit-result.php" data-result_div="editAuthMethodResult" data-form='editAuthMethod'>
            <i class="fa <?php print $POST->action == 'add' ? 'fa-plus' : ($POST->action == 'delete' ? 'fa-trash-o' : 'fa-check'); ?>"></i> <?php print $User->get_post_action(); ?>
        </button>
    </div>

    <?php
    // check for matching users
    if ($POST->action == 'delete') {
        $users = $Admin->fetch_multiple_objects('users', 'authMethod', @$method_settings->id);
        if (is_array($users)) {
            $Result->warn('warning', count($users)._(' users have this method for logging in. They will be reset to local auth!'));
        }
    }
?>

    <!-- Result -->
    <div id="editAuthMethodResult"></div>
</div>
