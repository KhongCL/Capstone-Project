<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* @MobileMessaging/index.twig */
class __TwigTemplate_bb3b4684525529352c3c8192ee30b570 extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'content' => [$this, 'block_content'],
        ];
    }

    protected function doGetParent(array $context)
    {
        // line 1
        return "admin.twig";
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 3
        $context["title"] = ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros, $blocks) {
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("MobileMessaging_SettingsMenu"), "html", null, true);
            return; yield '';
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 1
        $this->parent = $this->loadTemplate("admin.twig", "@MobileMessaging/index.twig", 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
    }

    // line 5
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 6
        yield "<div
    vue-entry=\"MobileMessaging.AdminPage\"
    delegate-management-options=\"";
        // line 8
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("delegateManagementOptions", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["delegateManagementOptions"]) || array_key_exists("delegateManagementOptions", $context) ? $context["delegateManagementOptions"] : (function () { throw new RuntimeError('Variable "delegateManagementOptions" does not exist.', 8, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
    delegated-management=\"";
        // line 9
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("delegatedManagement", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["delegatedManagement"]) || array_key_exists("delegatedManagement", $context) ? $context["delegatedManagement"] : (function () { throw new RuntimeError('Variable "delegatedManagement" does not exist.', 9, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
    credential-supplied=\"";
        // line 10
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("credentialSupplied", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["credentialSupplied"]) || array_key_exists("credentialSupplied", $context) ? $context["credentialSupplied"] : (function () { throw new RuntimeError('Variable "credentialSupplied" does not exist.', 10, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
    credential-error=\"";
        // line 11
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("credentialError", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["credentialError"]) || array_key_exists("credentialError", $context) ? $context["credentialError"] : (function () { throw new RuntimeError('Variable "credentialError" does not exist.', 11, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
    provider=\"";
        // line 12
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("provider", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["provider"]) || array_key_exists("provider", $context) ? $context["provider"] : (function () { throw new RuntimeError('Variable "provider" does not exist.', 12, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
    credit-left=\"";
        // line 13
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("creditLeft", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["creditLeft"]) || array_key_exists("creditLeft", $context) ? $context["creditLeft"] : (function () { throw new RuntimeError('Variable "creditLeft" does not exist.', 13, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
    sms-provider-options=\"";
        // line 14
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("smsProviderOptions", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["smsProviderOptions"]) || array_key_exists("smsProviderOptions", $context) ? $context["smsProviderOptions"] : (function () { throw new RuntimeError('Variable "smsProviderOptions" does not exist.', 14, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
    sms-providers=\"";
        // line 15
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("smsProviders", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["smsProviders"]) || array_key_exists("smsProviders", $context) ? $context["smsProviders"] : (function () { throw new RuntimeError('Variable "smsProviders" does not exist.', 15, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
    is-super-user=\"";
        // line 16
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("isSuperUser", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["isSuperUser"]) || array_key_exists("isSuperUser", $context) ? $context["isSuperUser"] : (function () { throw new RuntimeError('Variable "isSuperUser" does not exist.', 16, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
    default-calling-code=\"";
        // line 17
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("defaultCallingCode", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["defaultCallingCode"]) || array_key_exists("defaultCallingCode", $context) ? $context["defaultCallingCode"] : (function () { throw new RuntimeError('Variable "defaultCallingCode" does not exist.', 17, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
    countries=\"";
        // line 18
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("countries", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["countries"]) || array_key_exists("countries", $context) ? $context["countries"] : (function () { throw new RuntimeError('Variable "countries" does not exist.', 18, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
    str-help-add-phone=\"";
        // line 19
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("strHelpAddPhone", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["strHelpAddPhone"]) || array_key_exists("strHelpAddPhone", $context) ? $context["strHelpAddPhone"] : (function () { throw new RuntimeError('Variable "strHelpAddPhone" does not exist.', 19, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
    phone-numbers=\"";
        // line 20
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("phoneNumbers", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["phoneNumbers"]) || array_key_exists("phoneNumbers", $context) ? $context["phoneNumbers"] : (function () { throw new RuntimeError('Variable "phoneNumbers" does not exist.', 20, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
    account-managed-by-current-user=\"";
        // line 21
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["accountManagedByCurrentUser"]) || array_key_exists("accountManagedByCurrentUser", $context) ? $context["accountManagedByCurrentUser"] : (function () { throw new RuntimeError('Variable "accountManagedByCurrentUser" does not exist.', 21, $this->source); })())), "html", null, true);
        yield "\"
></div>
";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "@MobileMessaging/index.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable()
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo()
    {
        return array (  113 => 21,  109 => 20,  105 => 19,  101 => 18,  97 => 17,  93 => 16,  89 => 15,  85 => 14,  81 => 13,  77 => 12,  73 => 11,  69 => 10,  65 => 9,  61 => 8,  57 => 6,  53 => 5,  48 => 1,  43 => 3,  36 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("{% extends 'admin.twig' %}

{% set title %}{{ 'MobileMessaging_SettingsMenu'|translate }}{% endset %}

{% block content %}
<div
    vue-entry=\"MobileMessaging.AdminPage\"
    delegate-management-options=\"{{ delegateManagementOptions|default(null)|json_encode }}\"
    delegated-management=\"{{ delegatedManagement|default(null)|json_encode }}\"
    credential-supplied=\"{{ credentialSupplied|default(null)|json_encode }}\"
    credential-error=\"{{ credentialError|default(null)|json_encode }}\"
    provider=\"{{ provider|default(null)|json_encode }}\"
    credit-left=\"{{ creditLeft|default(null)|json_encode }}\"
    sms-provider-options=\"{{ smsProviderOptions|default(null)|json_encode }}\"
    sms-providers=\"{{ smsProviders|default(null)|json_encode }}\"
    is-super-user=\"{{ isSuperUser|default(null)|json_encode }}\"
    default-calling-code=\"{{ defaultCallingCode|default(null)|json_encode }}\"
    countries=\"{{ countries|default(null)|json_encode }}\"
    str-help-add-phone=\"{{ strHelpAddPhone|default(null)|json_encode }}\"
    phone-numbers=\"{{ phoneNumbers|default(null)|json_encode }}\"
    account-managed-by-current-user=\"{{ accountManagedByCurrentUser|json_encode }}\"
></div>
{% endblock %}
", "@MobileMessaging/index.twig", "C:\\xampp\\htdocs\\webTrafficAnalysis\\analytics\\plugins\\MobileMessaging\\templates\\index.twig");
    }
}
