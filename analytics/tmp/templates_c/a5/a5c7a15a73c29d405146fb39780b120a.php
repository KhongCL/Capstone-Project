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

/* @SitesManager/_trackingCodeEmail.twig */
class __TwigTemplate_16a4c2801a6191395cbe367ac3f5d85b extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        if ((isset($context["consentManagerName"]) || array_key_exists("consentManagerName", $context) ? $context["consentManagerName"] : (function () { throw new RuntimeError('Variable "consentManagerName" does not exist.', 1, $this->source); })())) {
            // line 2
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("CoreAdminHome_JSTracking_ConsentManagerDetected", (isset($context["consentManagerName"]) || array_key_exists("consentManagerName", $context) ? $context["consentManagerName"] : (function () { throw new RuntimeError('Variable "consentManagerName" does not exist.', 2, $this->source); })()), (isset($context["consentManagerUrl"]) || array_key_exists("consentManagerUrl", $context) ? $context["consentManagerUrl"] : (function () { throw new RuntimeError('Variable "consentManagerUrl" does not exist.', 2, $this->source); })())), "html", null, true);
            yield "

";
        }
        // line 5
        if (CoreExtension::inFilter("GoogleAnalytics3", (isset($context["trackers"]) || array_key_exists("trackers", $context) ? $context["trackers"] : (function () { throw new RuntimeError('Variable "trackers" does not exist.', 5, $this->source); })()))) {
            // line 6
            yield $this->env->getFilter('translate')->getCallable()("SitesManager_GADetectedEmail", "Google Analytics 3", "GA", $this->env->getFunction('externalrawlink')->getCallable()("https://matomo.org/faq/how-to/migrate-from-google-analytics-3-to-matomo/"));
            yield "

";
        }
        // line 9
        if (CoreExtension::inFilter("GoogleAnalytics4", (isset($context["trackers"]) || array_key_exists("trackers", $context) ? $context["trackers"] : (function () { throw new RuntimeError('Variable "trackers" does not exist.', 9, $this->source); })()))) {
            // line 10
            yield $this->env->getFilter('translate')->getCallable()("SitesManager_GADetectedEmail", "Google Analytics 4", "GA", $this->env->getFunction('externalrawlink')->getCallable()("https://matomo.org/faq/how-to/migrate-from-google-analytics-4-to-matomo/"));
            yield "

";
        }
        // line 13
        if (CoreExtension::inFilter("GoogleTagManager", (isset($context["trackers"]) || array_key_exists("trackers", $context) ? $context["trackers"] : (function () { throw new RuntimeError('Variable "trackers" does not exist.', 13, $this->source); })()))) {
            // line 14
            yield $this->env->getFilter('translate')->getCallable()("SitesManager_GTMDetectedEmail", $this->env->getFunction('externalrawlink')->getCallable()("https://matomo.org/faq/tag-manager/migrating-from-google-tag-manager/"));
            yield "

";
        }
        // line 17
        yield "
";
        // line 18
        if (CoreExtension::inFilter("VueJs", (isset($context["jsFrameworks"]) || array_key_exists("jsFrameworks", $context) ? $context["jsFrameworks"] : (function () { throw new RuntimeError('Variable "jsFrameworks" does not exist.', 18, $this->source); })()))) {
            // line 19
            yield "    ";
            yield $this->env->getFilter('translate')->getCallable()("SitesManager_VueDetectedEmail", "vue-matomo", $this->env->getFunction('externalrawlink')->getCallable()("https://matomo.org/faq/new-to-piwik/how-do-i-install-the-matomo-tracking-code-on-websites-that-use-vue-js/"));
            yield "

";
        }
        // line 22
        yield "
";
        // line 23
        if (CoreExtension::inFilter("ReactJs", (isset($context["jsFrameworks"]) || array_key_exists("jsFrameworks", $context) ? $context["jsFrameworks"] : (function () { throw new RuntimeError('Variable "jsFrameworks" does not exist.', 23, $this->source); })()))) {
            // line 24
            yield $this->env->getFilter('translate')->getCallable()("SitesManager_ReactDetectedEmail", $this->env->getFunction('externalrawlink')->getCallable()("https://matomo.org/faq/new-to-piwik/how-do-i-start-tracking-data-with-matomo-on-websites-that-use-react/"));
            yield "

";
        }
        // line 27
        if (((((((isset($context["consentManagerName"]) || array_key_exists("consentManagerName", $context) ? $context["consentManagerName"] : (function () { throw new RuntimeError('Variable "consentManagerName" does not exist.', 27, $this->source); })()) || CoreExtension::inFilter("GoogleAnalytics3", (isset($context["trackers"]) || array_key_exists("trackers", $context) ? $context["trackers"] : (function () { throw new RuntimeError('Variable "trackers" does not exist.', 27, $this->source); })()))) || CoreExtension::inFilter("GoogleAnalytics4", (isset($context["trackers"]) || array_key_exists("trackers", $context) ? $context["trackers"] : (function () { throw new RuntimeError('Variable "trackers" does not exist.', 27, $this->source); })()))) || CoreExtension::inFilter("GoogleTagManager", (isset($context["trackers"]) || array_key_exists("trackers", $context) ? $context["trackers"] : (function () { throw new RuntimeError('Variable "trackers" does not exist.', 27, $this->source); })()))) || CoreExtension::inFilter("Cloudflare", (isset($context["cms"]) || array_key_exists("cms", $context) ? $context["cms"] : (function () { throw new RuntimeError('Variable "cms" does not exist.', 27, $this->source); })()))) || CoreExtension::inFilter("VueJs", (isset($context["jsFrameworks"]) || array_key_exists("jsFrameworks", $context) ? $context["jsFrameworks"] : (function () { throw new RuntimeError('Variable "jsFrameworks" does not exist.', 27, $this->source); })())))) {
            // line 28
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("CoreAdminHome_JSTracking_ConsentManagerEmailNote"), "html", null, true);
            yield "

";
        }
        // line 31
        if ((isset($context["showMatomoLinks"]) || array_key_exists("showMatomoLinks", $context) ? $context["showMatomoLinks"] : (function () { throw new RuntimeError('Variable "showMatomoLinks" does not exist.', 31, $this->source); })())) {
            yield "** ";
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("General_JsTrackingTag"), "html", null, true);
        }
        // line 32
        yield "
";
        // line 33
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("CoreAdminHome_JSTracking_CodeNoteBeforeClosingHeadEmail", "'head'"), "html", null, true);
        yield "
";
        // line 34
        yield (isset($context["jsTag"]) || array_key_exists("jsTag", $context) ? $context["jsTag"] : (function () { throw new RuntimeError('Variable "jsTag" does not exist.', 34, $this->source); })());
        yield "

";
        // line 36
        if ((isset($context["showMatomoLinks"]) || array_key_exists("showMatomoLinks", $context) ? $context["showMatomoLinks"] : (function () { throw new RuntimeError('Variable "showMatomoLinks" does not exist.', 36, $this->source); })())) {
            yield $this->env->getFilter('translate')->getCallable()("SitesManager_EmailInstructionsDocsPlainText", $this->env->getFunction('externalrawlink')->getCallable()("https://developer.matomo.org/guides/tracking-javascript-guide"));
        }
        // line 37
        yield "
";
        // line 38
        yield $this->env->getFilter('translate')->getCallable()("SitesManager_EmailInstructionsGenerateTrackingCode", ((isset($context["piwikUrl"]) || array_key_exists("piwikUrl", $context) ? $context["piwikUrl"] : (function () { throw new RuntimeError('Variable "piwikUrl" does not exist.', 38, $this->source); })()) . $this->env->getFunction('linkTo')->getCallable()(["module" => "CoreAdminHome", "action" => "trackingCodeGenerator"])));
        yield "

";
        // line 40
        if ((isset($context["showMatomoLinks"]) || array_key_exists("showMatomoLinks", $context) ? $context["showMatomoLinks"] : (function () { throw new RuntimeError('Variable "showMatomoLinks" does not exist.', 40, $this->source); })())) {
            // line 41
            yield "** ";
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("SitesManager_InstallationGuides"), "html", null, true);
            yield "
WordPress: ";
            // line 42
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFunction('externalrawlink')->getCallable()("https://matomo.org/faq/new-to-piwik/how-do-i-install-the-matomo-tracking-code-on-wordpress/"), "html", null, true);
            yield "
Squarespace: ";
            // line 43
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFunction('externalrawlink')->getCallable()("https://matomo.org/faq/new-to-piwik/how-do-i-integrate-matomo-with-squarespace-website/"), "html", null, true);
            yield "
Wix: ";
            // line 44
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFunction('externalrawlink')->getCallable()("https://matomo.org/faq/new-to-piwik/how-do-i-install-the-matomo-analytics-tracking-code-on-wix/"), "html", null, true);
            yield "
SharePoint: ";
            // line 45
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFunction('externalrawlink')->getCallable()("https://matomo.org/faq/how-to-install/faq_19424/"), "html", null, true);
            yield "
Joomla: ";
            // line 46
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFunction('externalrawlink')->getCallable()("https://matomo.org/faq/new-to-piwik/how-do-i-install-the-matomo-analytics-tracking-code-on-joomla/"), "html", null, true);
            yield "
Shopify: ";
            // line 47
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFunction('externalrawlink')->getCallable()("https://matomo.org/faq/new-to-piwik/how-do-i-install-the-matomo-tracking-code-on-my-shopify-store/"), "html", null, true);
            yield "
Google Tag Manager: ";
            // line 48
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFunction('externalrawlink')->getCallable()("https://matomo.org/faq/new-to-piwik/how-do-i-use-matomo-analytics-within-gtm-google-tag-manager/"), "html", null, true);
            yield "
Cloudflare: ";
            // line 49
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFunction('externalrawlink')->getCallable()("https://matomo.org/faq/new-to-piwik/how-do-i-install-the-matomo-tracking-code-on-my-cloudflare-setup/"), "html", null, true);
            yield "
Vue.js: ";
            // line 50
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFunction('externalrawlink')->getCallable()("https://matomo.org/faq/new-to-piwik/how-do-i-install-the-matomo-tracking-code-on-websites-that-use-vue-js/"), "html", null, true);
            yield "
React: ";
            // line 51
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFunction('externalrawlink')->getCallable()("https://matomo.org/faq/new-to-piwik/how-do-i-start-tracking-data-with-matomo-on-websites-that-use-react/"), "html", null, true);
            yield "

** ";
            // line 53
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("SitesManager_Integrations"), "html", null, true);
            yield "
";
            // line 54
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("CoreAdminHome_JSTrackingIntro3a", "", ""), "html", null, true);
            yield "
";
            // line 55
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFunction('externalrawlink')->getCallable()("https://matomo.org/integrate/"), "html", null, true);
            yield "

** ";
            // line 57
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("SitesManager_LogAnalytics"), "html", null, true);
            yield "
";
            // line 58
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("SitesManager_LogAnalyticsDescription", "", ""), "html", null, true);
            yield "
";
            // line 59
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFunction('externalrawlink')->getCallable()("https://matomo.org/log-analytics/"), "html", null, true);
            yield "

** ";
            // line 61
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("SitesManager_MobileAppsAndSDKs"), "html", null, true);
            yield "
";
            // line 62
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("SitesManager_MobileAppsAndSDKsDescription", "", ""), "html", null, true);
            yield "
";
            // line 63
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFunction('externalrawlink')->getCallable()("https://matomo.org/integrate/#programming-language-platforms-and-frameworks"), "html", null, true);
            yield "

** ";
            // line 65
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("CoreAdminHome_HttpTrackingApi"), "html", null, true);
            yield "
";
            // line 66
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("CoreAdminHome_HttpTrackingApiDescription", "", ""), "html", null, true);
            yield "
";
            // line 67
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFunction('externalrawlink')->getCallable()("https://developer.matomo.org/api-reference/tracking-api"), "html", null, true);
            yield "

** ";
            // line 69
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("SitesManager_SiteWithoutDataSinglePageApplication"), "html", null, true);
            yield "
";
            // line 70
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("CoreAdminHome_SinglePageApplicationDescription", "", ""), "html", null, true);
            yield "
";
            // line 71
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFunction('externalrawlink')->getCallable()("https://developer.matomo.org/guides/spa-tracking"), "html", null, true);
            yield "

** Cloudflare
";
            // line 74
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("CoreAdminHome_CloudflareDescription", "", ""), "html", null, true);
            yield "
";
            // line 75
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFunction('externalrawlink')->getCallable()("https://matomo.org/faq/new-to-piwik/how-do-i-install-the-matomo-tracking-code-on-my-cloudflare-setup/"), "html", null, true);
            yield "

** Vue.js
";
            // line 78
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("CoreAdminHome_VueDescription", "", ""), "html", null, true);
            yield "
";
            // line 79
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFunction('externalrawlink')->getCallable()("https://matomo.org/faq/new-to-piwik/how-do-i-install-the-matomo-tracking-code-on-websites-that-use-vue-js/"), "html", null, true);
            yield "

** React.js
";
            // line 82
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("CoreAdminHome_ReactDescription", "", ""), "html", null, true);
            yield "
";
            // line 83
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFunction('externalrawlink')->getCallable()("https://matomo.org/faq/new-to-piwik/how-do-i-start-tracking-data-with-matomo-on-websites-that-use-react/"), "html", null, true);
            yield "

** ";
            // line 85
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("SitesManager_EmailInstructionsSiteDetailsHeading"), "html", null, true);
            yield "
";
            // line 86
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("SitesManager_EmailInstructionsSiteDetails"), "html", null, true);
            yield "
* ";
            // line 87
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("SitesManager_EmailInstructionsYourSiteId", (isset($context["idSite"]) || array_key_exists("idSite", $context) ? $context["idSite"] : (function () { throw new RuntimeError('Variable "idSite" does not exist.', 87, $this->source); })())), "html", null, true);
            yield "
* ";
            // line 88
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("SitesManager_EmailInstructionsYourTrackingUrl", (isset($context["trackingUrl"]) || array_key_exists("trackingUrl", $context) ? $context["trackingUrl"] : (function () { throw new RuntimeError('Variable "trackingUrl" does not exist.', 88, $this->source); })())), "html", null, true);
            yield "
";
        }
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "@SitesManager/_trackingCodeEmail.twig";
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
        return array (  285 => 88,  281 => 87,  277 => 86,  273 => 85,  268 => 83,  264 => 82,  258 => 79,  254 => 78,  248 => 75,  244 => 74,  238 => 71,  234 => 70,  230 => 69,  225 => 67,  221 => 66,  217 => 65,  212 => 63,  208 => 62,  204 => 61,  199 => 59,  195 => 58,  191 => 57,  186 => 55,  182 => 54,  178 => 53,  173 => 51,  169 => 50,  165 => 49,  161 => 48,  157 => 47,  153 => 46,  149 => 45,  145 => 44,  141 => 43,  137 => 42,  132 => 41,  130 => 40,  125 => 38,  122 => 37,  118 => 36,  113 => 34,  109 => 33,  106 => 32,  101 => 31,  95 => 28,  93 => 27,  87 => 24,  85 => 23,  82 => 22,  75 => 19,  73 => 18,  70 => 17,  64 => 14,  62 => 13,  56 => 10,  54 => 9,  48 => 6,  46 => 5,  40 => 2,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("{% if consentManagerName %}
{{ 'CoreAdminHome_JSTracking_ConsentManagerDetected'|translate(consentManagerName, consentManagerUrl) }}

{% endif %}
{% if 'GoogleAnalytics3' in trackers %}
{{ 'SitesManager_GADetectedEmail'|translate('Google Analytics 3', 'GA', externalrawlink('https://matomo.org/faq/how-to/migrate-from-google-analytics-3-to-matomo/'))|raw }}

{% endif %}
{% if 'GoogleAnalytics4' in trackers %}
{{ 'SitesManager_GADetectedEmail'|translate('Google Analytics 4', 'GA', externalrawlink('https://matomo.org/faq/how-to/migrate-from-google-analytics-4-to-matomo/'))|raw }}

{% endif %}
{% if 'GoogleTagManager' in trackers %}
{{ 'SitesManager_GTMDetectedEmail'|translate(externalrawlink('https://matomo.org/faq/tag-manager/migrating-from-google-tag-manager/'))|raw }}

{% endif %}

{% if 'VueJs' in jsFrameworks %}
    {{ 'SitesManager_VueDetectedEmail'|translate('vue-matomo', externalrawlink('https://matomo.org/faq/new-to-piwik/how-do-i-install-the-matomo-tracking-code-on-websites-that-use-vue-js/'))|raw }}

{% endif %}

{% if 'ReactJs' in jsFrameworks %}
{{ 'SitesManager_ReactDetectedEmail'|translate(externalrawlink('https://matomo.org/faq/new-to-piwik/how-do-i-start-tracking-data-with-matomo-on-websites-that-use-react/'))|raw }}

{% endif %}
{% if consentManagerName or 'GoogleAnalytics3' in trackers or 'GoogleAnalytics4' in trackers or 'GoogleTagManager' in trackers or 'Cloudflare' in cms or 'VueJs' in jsFrameworks %}
{{ 'CoreAdminHome_JSTracking_ConsentManagerEmailNote'|translate }}

{% endif %}
{% if showMatomoLinks %}** {{ 'General_JsTrackingTag'|translate }}{% endif %}

{{ 'CoreAdminHome_JSTracking_CodeNoteBeforeClosingHeadEmail'|translate(\"'head'\") }}
{{ jsTag|raw }}

{% if showMatomoLinks %}{{ 'SitesManager_EmailInstructionsDocsPlainText'|translate(externalrawlink('https://developer.matomo.org/guides/tracking-javascript-guide'))|raw }}{% endif %}

{{ 'SitesManager_EmailInstructionsGenerateTrackingCode'|translate(piwikUrl ~ linkTo({'module': 'CoreAdminHome', 'action': 'trackingCodeGenerator'}))|raw }}

{% if showMatomoLinks %}
** {{ 'SitesManager_InstallationGuides'|translate }}
WordPress: {{ externalrawlink('https://matomo.org/faq/new-to-piwik/how-do-i-install-the-matomo-tracking-code-on-wordpress/') }}
Squarespace: {{ externalrawlink('https://matomo.org/faq/new-to-piwik/how-do-i-integrate-matomo-with-squarespace-website/') }}
Wix: {{ externalrawlink('https://matomo.org/faq/new-to-piwik/how-do-i-install-the-matomo-analytics-tracking-code-on-wix/') }}
SharePoint: {{ externalrawlink('https://matomo.org/faq/how-to-install/faq_19424/') }}
Joomla: {{ externalrawlink('https://matomo.org/faq/new-to-piwik/how-do-i-install-the-matomo-analytics-tracking-code-on-joomla/') }}
Shopify: {{ externalrawlink('https://matomo.org/faq/new-to-piwik/how-do-i-install-the-matomo-tracking-code-on-my-shopify-store/') }}
Google Tag Manager: {{ externalrawlink('https://matomo.org/faq/new-to-piwik/how-do-i-use-matomo-analytics-within-gtm-google-tag-manager/') }}
Cloudflare: {{ externalrawlink('https://matomo.org/faq/new-to-piwik/how-do-i-install-the-matomo-tracking-code-on-my-cloudflare-setup/') }}
Vue.js: {{ externalrawlink('https://matomo.org/faq/new-to-piwik/how-do-i-install-the-matomo-tracking-code-on-websites-that-use-vue-js/') }}
React: {{ externalrawlink('https://matomo.org/faq/new-to-piwik/how-do-i-start-tracking-data-with-matomo-on-websites-that-use-react/') }}

** {{ 'SitesManager_Integrations'|translate }}
{{ 'CoreAdminHome_JSTrackingIntro3a'|translate('', '') }}
{{ externalrawlink('https://matomo.org/integrate/') }}

** {{ 'SitesManager_LogAnalytics'|translate }}
{{ 'SitesManager_LogAnalyticsDescription'|translate('', '') }}
{{ externalrawlink('https://matomo.org/log-analytics/') }}

** {{ 'SitesManager_MobileAppsAndSDKs'|translate }}
{{ 'SitesManager_MobileAppsAndSDKsDescription'|translate('', '') }}
{{ externalrawlink('https://matomo.org/integrate/#programming-language-platforms-and-frameworks') }}

** {{ 'CoreAdminHome_HttpTrackingApi'|translate }}
{{ 'CoreAdminHome_HttpTrackingApiDescription'|translate('', '') }}
{{ externalrawlink('https://developer.matomo.org/api-reference/tracking-api') }}

** {{ 'SitesManager_SiteWithoutDataSinglePageApplication'|translate }}
{{ 'CoreAdminHome_SinglePageApplicationDescription'|translate('', '') }}
{{ externalrawlink('https://developer.matomo.org/guides/spa-tracking') }}

** Cloudflare
{{ 'CoreAdminHome_CloudflareDescription'|translate('', '') }}
{{ externalrawlink('https://matomo.org/faq/new-to-piwik/how-do-i-install-the-matomo-tracking-code-on-my-cloudflare-setup/') }}

** Vue.js
{{ 'CoreAdminHome_VueDescription'|translate('', '') }}
{{ externalrawlink('https://matomo.org/faq/new-to-piwik/how-do-i-install-the-matomo-tracking-code-on-websites-that-use-vue-js/') }}

** React.js
{{ 'CoreAdminHome_ReactDescription'|translate('', '') }}
{{ externalrawlink('https://matomo.org/faq/new-to-piwik/how-do-i-start-tracking-data-with-matomo-on-websites-that-use-react/') }}

** {{ 'SitesManager_EmailInstructionsSiteDetailsHeading'|translate }}
{{ 'SitesManager_EmailInstructionsSiteDetails'|translate }}
* {{ 'SitesManager_EmailInstructionsYourSiteId'|translate(idSite) }}
* {{ 'SitesManager_EmailInstructionsYourTrackingUrl'|translate(trackingUrl) }}
{% endif %}
", "@SitesManager/_trackingCodeEmail.twig", "C:\\xampp\\htdocs\\webTrafficAnalysis\\analytics\\plugins\\SitesManager\\templates\\_trackingCodeEmail.twig");
    }
}
