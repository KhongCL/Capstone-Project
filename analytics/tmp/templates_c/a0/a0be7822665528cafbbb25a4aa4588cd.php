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

/* @SitesManager/_displayJavascriptCode.twig */
class __TwigTemplate_b816421413dab4f42ea83c7f8b511ccd extends Template
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
        yield "<div class=\"trackingHelpHeader\">
    <h2>";
        // line 2
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("SitesManager_TrackingTags", (isset($context["displaySiteName"]) || array_key_exists("displaySiteName", $context) ? $context["displaySiteName"] : (function () { throw new RuntimeError('Variable "displaySiteName" does not exist.', 2, $this->source); })())), "html", null, true);
        yield "</h2>
    <a class=\"btn\"
       target=\"_blank\"
       href=\"mailto:?subject=";
        // line 5
        yield \Piwik\piwik_escape_filter($this->env, Twig\Extension\CoreExtension::urlencode($this->env->getFilter('translate')->getCallable()("SitesManager_EmailInstructionsSubject")), "html", null, true);
        yield "&body=";
        yield \Piwik\piwik_escape_filter($this->env, Twig\Extension\CoreExtension::urlencode((isset($context["emailBody"]) || array_key_exists("emailBody", $context) ? $context["emailBody"] : (function () { throw new RuntimeError('Variable "emailBody" does not exist.', 5, $this->source); })())), "html", null, true);
        yield "\">
        ";
        // line 6
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("SitesManager_EmailInstructionsButton"), "html", null, true);
        yield "
    </a>
</div>

<div class='trackingHelp'>
    <p>";
        // line 11
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("Installation_JSTracking_Intro"), "html", null, true);
        yield "</p>

    <p>";
        // line 13
        yield $this->env->getFilter('translate')->getCallable()("CoreAdminHome_JSTrackingIntro3a", $this->env->getFunction('externallink')->getCallable()("https://matomo.org/integrate/"), "</a>");
        yield " ";
        yield $this->env->getFilter('translate')->getCallable()("CoreAdminHome_JSTrackingIntro3b");
        yield "</p>

    <h3>";
        // line 15
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("General_JsTrackingTag"), "html", null, true);
        yield "</h3>

    <p>";
        // line 17
        yield $this->env->getFilter('translate')->getCallable()("CoreAdminHome_JSTracking_CodeNoteBeforeClosingHead", "&lt;/head&gt;");
        yield "</p>

    <div>
        <pre vue-directive=\"CoreHome.CopyToClipboard\">";
        // line 20
        yield (isset($context["jsTag"]) || array_key_exists("jsTag", $context) ? $context["jsTag"] : (function () { throw new RuntimeError('Variable "jsTag" does not exist.', 20, $this->source); })());
        yield "</pre>
    </div>

    <p>";
        // line 23
        yield $this->env->getFilter('translate')->getCallable()("CoreAdminHome_JSTrackingIntro5", $this->env->getFunction('externallink')->getCallable()("https://developer.matomo.org/guides/tracking-javascript-guide"), "</a>");
        yield "</p>

    ";
        // line 25
        if (array_key_exists("isInstall", $context)) {
            // line 26
            yield "        <p>";
            yield $this->env->getFilter('translate')->getCallable()("Installation_JSTracking_EndNote", "", "");
            yield "</p>
    ";
        } else {
            // line 28
            yield "        <p>";
            yield $this->env->getFilter('translate')->getCallable()("CoreAdminHome_JSTracking_EndNote", (("<a href=\"" . $this->env->getFunction('linkTo')->getCallable()(["module" => "CoreAdminHome", "action" => "trackingCodeGenerator"])) . "\">"), "</a>");
            yield "</p>
    ";
        }
        // line 30
        yield "
    <h3>";
        // line 31
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("SitesManager_LogAnalytics"), "html", null, true);
        yield "</h3>

    <p>";
        // line 33
        yield $this->env->getFilter('translate')->getCallable()("SitesManager_LogAnalyticsDescription", $this->env->getFunction('externallink')->getCallable()("https://matomo.org/log-analytics/"), "</a>");
        yield "</p>

    <h3>";
        // line 35
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("SitesManager_MobileAppsAndSDKs"), "html", null, true);
        yield "</h3>

    <p>";
        // line 37
        yield $this->env->getFilter('translate')->getCallable()("SitesManager_MobileAppsAndSDKsDescription", $this->env->getFunction('externallink')->getCallable()("https://matomo.org/integrate/#programming-language-platforms-and-frameworks"), "</a>");
        yield "</p>

    <h3>";
        // line 39
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("CoreAdminHome_HttpTrackingApi"), "html", null, true);
        yield "</h3>
    <p>";
        // line 40
        yield $this->env->getFilter('translate')->getCallable()("CoreAdminHome_HttpTrackingApiDescription", $this->env->getFunction('externallink')->getCallable()("https://developer.matomo.org/api-reference/tracking-api"), "</a>");
        yield "</p>

    <h3>";
        // line 42
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("SitesManager_SiteWithoutDataSinglePageApplication"), "html", null, true);
        yield "</h3>
    <p>";
        // line 43
        yield $this->env->getFilter('translate')->getCallable()("SitesManager_SiteWithoutDataSinglePageApplicationDescription", $this->env->getFunction('externallink')->getCallable()("https://developer.matomo.org/guides/spa-tracking"), "</a>");
        yield "</p>

    <h3>";
        // line 45
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("SitesManager_SiteWithoutDataGoogleTagManager"), "html", null, true);
        yield "</h3>
    <p>";
        // line 46
        yield $this->env->getFilter('translate')->getCallable()("SitesManager_SiteWithoutDataGoogleTagManagerDescription", $this->env->getFunction('externallink')->getCallable()("https://matomo.org/faq/new-to-piwik/how-do-i-use-matomo-analytics-within-gtm-google-tag-manager/"), "</a>");
        yield "</p>

    <h3>WordPress</h3>
    <p>";
        // line 49
        yield $this->env->getFilter('translate')->getCallable()("SitesManager_SiteWithoutDataWordpressDescription", $this->env->getFunction('externallink')->getCallable()("https://matomo.org/faq/new-to-piwik/how-do-i-install-the-matomo-tracking-code-on-wordpress/"), "</a>");
        yield "</p>

    <h3>Vue.js</h3>
    <p>";
        // line 52
        yield $this->env->getFilter('translate')->getCallable()("SitesManager_SiteWithoutDataVueDescription", ($this->env->getFunction('externallink')->getCallable()("https://github.com/AmazingDreams/vue-matomo") . "vue-matomo</a>"), $this->env->getFunction('externallink')->getCallable()("https://matomo.org/faq/new-to-piwik/how-do-i-install-the-matomo-tracking-code-on-websites-that-use-vue-js/"), "</a>");
        yield "</p>

    <h3>React.js</h3>
    <p>";
        // line 55
        yield $this->env->getFilter('translate')->getCallable()("SitesManager_SiteWithoutDataReactDescription", $this->env->getFunction('externallink')->getCallable()("https://matomo.org/guide/tag-manager/"), "</a>", $this->env->getFunction('externallink')->getCallable()("https://matomo.org/faq/new-to-piwik/how-do-i-start-tracking-data-with-matomo-on-websites-that-use-react/"), "</a>");
        yield "</p>

    <p></p>
    ";
        // line 58
        yield $this->env->getFunction('postEvent')->getCallable()("Template.endTrackingHelpPage");
        yield "
</div>
";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "@SitesManager/_displayJavascriptCode.twig";
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
        return array (  178 => 58,  172 => 55,  166 => 52,  160 => 49,  154 => 46,  150 => 45,  145 => 43,  141 => 42,  136 => 40,  132 => 39,  127 => 37,  122 => 35,  117 => 33,  112 => 31,  109 => 30,  103 => 28,  97 => 26,  95 => 25,  90 => 23,  84 => 20,  78 => 17,  73 => 15,  66 => 13,  61 => 11,  53 => 6,  47 => 5,  41 => 2,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("<div class=\"trackingHelpHeader\">
    <h2>{{ 'SitesManager_TrackingTags'|translate(displaySiteName) }}</h2>
    <a class=\"btn\"
       target=\"_blank\"
       href=\"mailto:?subject={{ 'SitesManager_EmailInstructionsSubject'|translate|url_encode }}&body={{ emailBody|url_encode }}\">
        {{ 'SitesManager_EmailInstructionsButton'|translate }}
    </a>
</div>

<div class='trackingHelp'>
    <p>{{ 'Installation_JSTracking_Intro'|translate }}</p>

    <p>{{ 'CoreAdminHome_JSTrackingIntro3a'|translate(externallink('https://matomo.org/integrate/'),'</a>')|raw }} {{ 'CoreAdminHome_JSTrackingIntro3b'|translate|raw }}</p>

    <h3>{{ 'General_JsTrackingTag'|translate }}</h3>

    <p>{{ 'CoreAdminHome_JSTracking_CodeNoteBeforeClosingHead'|translate(\"&lt;/head&gt;\")|raw }}</p>

    <div>
        <pre vue-directive=\"CoreHome.CopyToClipboard\">{{ jsTag|raw }}</pre>
    </div>

    <p>{{ 'CoreAdminHome_JSTrackingIntro5'|translate(externallink('https://developer.matomo.org/guides/tracking-javascript-guide'),'</a>')|raw }}</p>

    {% if isInstall is defined %}
        <p>{{ 'Installation_JSTracking_EndNote'|translate('', '')|raw }}</p>
    {% else %}
        <p>{{ 'CoreAdminHome_JSTracking_EndNote'|translate('<a href=\"' ~ linkTo({'module': 'CoreAdminHome', 'action': 'trackingCodeGenerator'}) ~'\">','</a>')|raw }}</p>
    {% endif %}

    <h3>{{ 'SitesManager_LogAnalytics'|translate }}</h3>

    <p>{{ 'SitesManager_LogAnalyticsDescription'|translate(externallink('https://matomo.org/log-analytics/'), '</a>')|raw }}</p>

    <h3>{{ 'SitesManager_MobileAppsAndSDKs'|translate }}</h3>

    <p>{{ 'SitesManager_MobileAppsAndSDKsDescription'|translate(externallink('https://matomo.org/integrate/#programming-language-platforms-and-frameworks'),'</a>')|raw }}</p>

    <h3>{{ 'CoreAdminHome_HttpTrackingApi'|translate }}</h3>
    <p>{{ 'CoreAdminHome_HttpTrackingApiDescription'|translate(externallink('https://developer.matomo.org/api-reference/tracking-api'),'</a>')|raw }}</p>

    <h3>{{ 'SitesManager_SiteWithoutDataSinglePageApplication'|translate }}</h3>
    <p>{{ 'SitesManager_SiteWithoutDataSinglePageApplicationDescription'|translate(externallink('https://developer.matomo.org/guides/spa-tracking'),'</a>')|raw }}</p>

    <h3>{{ 'SitesManager_SiteWithoutDataGoogleTagManager'|translate }}</h3>
    <p>{{ 'SitesManager_SiteWithoutDataGoogleTagManagerDescription'|translate(externallink('https://matomo.org/faq/new-to-piwik/how-do-i-use-matomo-analytics-within-gtm-google-tag-manager/'),'</a>')|raw }}</p>

    <h3>WordPress</h3>
    <p>{{ 'SitesManager_SiteWithoutDataWordpressDescription'|translate(externallink('https://matomo.org/faq/new-to-piwik/how-do-i-install-the-matomo-tracking-code-on-wordpress/'),'</a>')|raw }}</p>

    <h3>Vue.js</h3>
    <p>{{ 'SitesManager_SiteWithoutDataVueDescription'|translate(externallink('https://github.com/AmazingDreams/vue-matomo') ~ 'vue-matomo</a>', externallink('https://matomo.org/faq/new-to-piwik/how-do-i-install-the-matomo-tracking-code-on-websites-that-use-vue-js/'),'</a>')|raw }}</p>

    <h3>React.js</h3>
    <p>{{ 'SitesManager_SiteWithoutDataReactDescription'|translate(externallink('https://matomo.org/guide/tag-manager/'), '</a>', externallink('https://matomo.org/faq/new-to-piwik/how-do-i-start-tracking-data-with-matomo-on-websites-that-use-react/'),'</a>')|raw }}</p>

    <p></p>
    {{ postEvent('Template.endTrackingHelpPage') }}
</div>
", "@SitesManager/_displayJavascriptCode.twig", "C:\\xampp\\htdocs\\webTrafficAnalysis\\analytics\\plugins\\SitesManager\\templates\\_displayJavascriptCode.twig");
    }
}
