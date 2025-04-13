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

/* @UsersManager/userSettings.twig */
class __TwigTemplate_356b42fbc06de59c8fef4aacab9abcf2 extends Template
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
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("UsersManager_PersonalSettings"), "html", null, true);
            return; yield '';
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 1
        $this->parent = $this->loadTemplate("admin.twig", "@UsersManager/userSettings.twig", 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
    }

    // line 5
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 6
        yield "<div
    vue-entry=\"UsersManager.UserSettings\"
    is-users-admin-enabled=\"";
        // line 8
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["isUsersAdminEnabled"]) || array_key_exists("isUsersAdminEnabled", $context) ? $context["isUsersAdminEnabled"] : (function () { throw new RuntimeError('Variable "isUsersAdminEnabled" does not exist.', 8, $this->source); })())), "html", null, true);
        yield "\"
    user-login=\"";
        // line 9
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["userLogin"]) || array_key_exists("userLogin", $context) ? $context["userLogin"] : (function () { throw new RuntimeError('Variable "userLogin" does not exist.', 9, $this->source); })())), "html", null, true);
        yield "\"
    user-email=\"";
        // line 10
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["userEmail"]) || array_key_exists("userEmail", $context) ? $context["userEmail"] : (function () { throw new RuntimeError('Variable "userEmail" does not exist.', 10, $this->source); })())), "html", null, true);
        yield "\"
    current-language-code=\"";
        // line 11
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["currentLanguageCode"]) || array_key_exists("currentLanguageCode", $context) ? $context["currentLanguageCode"] : (function () { throw new RuntimeError('Variable "currentLanguageCode" does not exist.', 11, $this->source); })())), "html", null, true);
        yield "\"
    language-options=\"";
        // line 12
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["languageOptions"]) || array_key_exists("languageOptions", $context) ? $context["languageOptions"] : (function () { throw new RuntimeError('Variable "languageOptions" does not exist.', 12, $this->source); })())), "html", null, true);
        yield "\"
    current-timeformat=\"";
        // line 13
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["currentTimeformat"]) || array_key_exists("currentTimeformat", $context) ? $context["currentTimeformat"] : (function () { throw new RuntimeError('Variable "currentTimeformat" does not exist.', 13, $this->source); })())), "html", null, true);
        yield "\"
    time-formats=\"";
        // line 14
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["timeFormats"]) || array_key_exists("timeFormats", $context) ? $context["timeFormats"] : (function () { throw new RuntimeError('Variable "timeFormats" does not exist.', 14, $this->source); })())), "html", null, true);
        yield "\"
    default-report=\"";
        // line 15
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["defaultReport"]) || array_key_exists("defaultReport", $context) ? $context["defaultReport"] : (function () { throw new RuntimeError('Variable "defaultReport" does not exist.', 15, $this->source); })())), "html", null, true);
        yield "\"
    default-report-options=\"";
        // line 16
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["defaultReportOptions"]) || array_key_exists("defaultReportOptions", $context) ? $context["defaultReportOptions"] : (function () { throw new RuntimeError('Variable "defaultReportOptions" does not exist.', 16, $this->source); })())), "html", null, true);
        yield "\"
    default-report-id-site=\"";
        // line 17
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["defaultReportIdSite"]) || array_key_exists("defaultReportIdSite", $context) ? $context["defaultReportIdSite"] : (function () { throw new RuntimeError('Variable "defaultReportIdSite" does not exist.', 17, $this->source); })())), "html", null, true);
        yield "\"
    default-report-site-name=\"";
        // line 18
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["defaultReportSiteName"]) || array_key_exists("defaultReportSiteName", $context) ? $context["defaultReportSiteName"] : (function () { throw new RuntimeError('Variable "defaultReportSiteName" does not exist.', 18, $this->source); })())), "html", null, true);
        yield "\"
    default-date=\"";
        // line 19
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["defaultDate"]) || array_key_exists("defaultDate", $context) ? $context["defaultDate"] : (function () { throw new RuntimeError('Variable "defaultDate" does not exist.', 19, $this->source); })())), "html", null, true);
        yield "\"
    available-default-dates=\"";
        // line 20
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["availableDefaultDates"]) || array_key_exists("availableDefaultDates", $context) ? $context["availableDefaultDates"] : (function () { throw new RuntimeError('Variable "availableDefaultDates" does not exist.', 20, $this->source); })())), "html", null, true);
        yield "\"
    show-newsletter-signup=\"";
        // line 21
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("showNewsletterSignup", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["showNewsletterSignup"]) || array_key_exists("showNewsletterSignup", $context) ? $context["showNewsletterSignup"] : (function () { throw new RuntimeError('Variable "showNewsletterSignup" does not exist.', 21, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
    ignore-cookie-set=\"";
        // line 22
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("ignoreCookieSet", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["ignoreCookieSet"]) || array_key_exists("ignoreCookieSet", $context) ? $context["ignoreCookieSet"] : (function () { throw new RuntimeError('Variable "ignoreCookieSet" does not exist.', 22, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
    set-ignore-cookie-nonce=\"";
        // line 23
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("setIgnoreCookieNonce", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["setIgnoreCookieNonce"]) || array_key_exists("setIgnoreCookieNonce", $context) ? $context["setIgnoreCookieNonce"] : (function () { throw new RuntimeError('Variable "setIgnoreCookieNonce" does not exist.', 23, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
    piwik-host=\"";
        // line 24
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["piwikHost"]) || array_key_exists("piwikHost", $context) ? $context["piwikHost"] : (function () { throw new RuntimeError('Variable "piwikHost" does not exist.', 24, $this->source); })())), "html", null, true);
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
        return "@UsersManager/userSettings.twig";
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
        return array (  125 => 24,  121 => 23,  117 => 22,  113 => 21,  109 => 20,  105 => 19,  101 => 18,  97 => 17,  93 => 16,  89 => 15,  85 => 14,  81 => 13,  77 => 12,  73 => 11,  69 => 10,  65 => 9,  61 => 8,  57 => 6,  53 => 5,  48 => 1,  43 => 3,  36 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("{% extends 'admin.twig' %}

{% set title %}{{ 'UsersManager_PersonalSettings'|translate }}{% endset %}

{% block content %}
<div
    vue-entry=\"UsersManager.UserSettings\"
    is-users-admin-enabled=\"{{ isUsersAdminEnabled|json_encode }}\"
    user-login=\"{{ userLogin|json_encode }}\"
    user-email=\"{{ userEmail|json_encode }}\"
    current-language-code=\"{{ currentLanguageCode|json_encode }}\"
    language-options=\"{{ languageOptions|json_encode }}\"
    current-timeformat=\"{{ currentTimeformat|json_encode }}\"
    time-formats=\"{{ timeFormats|json_encode }}\"
    default-report=\"{{ defaultReport|json_encode }}\"
    default-report-options=\"{{ defaultReportOptions|json_encode }}\"
    default-report-id-site=\"{{ defaultReportIdSite|json_encode }}\"
    default-report-site-name=\"{{ defaultReportSiteName|json_encode }}\"
    default-date=\"{{ defaultDate|json_encode }}\"
    available-default-dates=\"{{ availableDefaultDates|json_encode }}\"
    show-newsletter-signup=\"{{ showNewsletterSignup|default(null)|json_encode }}\"
    ignore-cookie-set=\"{{ ignoreCookieSet|default(null)|json_encode }}\"
    set-ignore-cookie-nonce=\"{{ setIgnoreCookieNonce|default(null)|json_encode }}\"
    piwik-host=\"{{ piwikHost|json_encode }}\"
></div>
{% endblock %}
", "@UsersManager/userSettings.twig", "C:\\xampp\\htdocs\\webTrafficAnalysis\\analytics\\plugins\\UsersManager\\templates\\userSettings.twig");
    }
}
