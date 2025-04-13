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

/* _jsGlobalVariables.twig */
class __TwigTemplate_ba1294014c82ae4316c8bf4d90cb8a7a extends Template
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
        yield "<script type=\"text/javascript\">
    var piwik = {};
    piwik.pluginsToLoadOnDemand = ";
        // line 3
        yield json_encode((isset($context["pluginsToLoadOnDemand"]) || array_key_exists("pluginsToLoadOnDemand", $context) ? $context["pluginsToLoadOnDemand"] : (function () { throw new RuntimeError('Variable "pluginsToLoadOnDemand" does not exist.', 3, $this->source); })()));
        yield ";
    piwik.token_auth = \"";
        // line 4
        yield \Piwik\piwik_escape_filter($this->env, (isset($context["token_auth"]) || array_key_exists("token_auth", $context) ? $context["token_auth"] : (function () { throw new RuntimeError('Variable "token_auth" does not exist.', 4, $this->source); })()), "html", null, true);
        yield "\";
    piwik.piwik_url = \"";
        // line 5
        yield \Piwik\piwik_escape_filter($this->env, (isset($context["piwikUrl"]) || array_key_exists("piwikUrl", $context) ? $context["piwikUrl"] : (function () { throw new RuntimeError('Variable "piwikUrl" does not exist.', 5, $this->source); })()), "html", null, true);
        yield "\";
    piwik.cacheBuster = \"";
        // line 6
        yield \Piwik\piwik_escape_filter($this->env, (isset($context["cacheBuster"]) || array_key_exists("cacheBuster", $context) ? $context["cacheBuster"] : (function () { throw new RuntimeError('Variable "cacheBuster" does not exist.', 6, $this->source); })()), "html", null, true);
        yield "\";
    piwik.disableTrackingMatomoAppLinks = ";
        // line 7
        yield \Piwik\piwik_escape_filter($this->env, (isset($context["disableTrackingMatomoAppLinks"]) || array_key_exists("disableTrackingMatomoAppLinks", $context) ? $context["disableTrackingMatomoAppLinks"] : (function () { throw new RuntimeError('Variable "disableTrackingMatomoAppLinks" does not exist.', 7, $this->source); })()), "html", null, true);
        yield ";
    ";
        // line 8
        if (array_key_exists("timezoneOffset", $context)) {
            yield "piwik.timezoneOffset = ";
            yield \Piwik\piwik_escape_filter($this->env, (isset($context["timezoneOffset"]) || array_key_exists("timezoneOffset", $context) ? $context["timezoneOffset"] : (function () { throw new RuntimeError('Variable "timezoneOffset" does not exist.', 8, $this->source); })()), "html", null, true);
            yield ";";
        }
        // line 9
        yield "
    piwik.numbers = {
        patternNumber: ";
        // line 11
        yield json_encode($this->env->getFilter('translate')->getCallable()("Intl_NumberFormatNumber"));
        yield ",
        patternPercent: ";
        // line 12
        yield json_encode($this->env->getFilter('translate')->getCallable()("Intl_NumberFormatPercent"));
        yield ",
        patternCurrency: ";
        // line 13
        yield json_encode($this->env->getFilter('translate')->getCallable()("Intl_NumberFormatCurrency"));
        yield ",
        symbolPlus: ";
        // line 14
        yield json_encode($this->env->getFilter('translate')->getCallable()("Intl_NumberSymbolPlus"));
        yield ",
        symbolMinus: ";
        // line 15
        yield json_encode($this->env->getFilter('translate')->getCallable()("Intl_NumberSymbolMinus"));
        yield ",
        symbolPercent: ";
        // line 16
        yield json_encode($this->env->getFilter('translate')->getCallable()("Intl_NumberSymbolPercent"));
        yield ",
        symbolGroup: ";
        // line 17
        yield json_encode($this->env->getFilter('translate')->getCallable()("Intl_NumberSymbolGroup"));
        yield ",
        symbolDecimal: ";
        // line 18
        yield json_encode($this->env->getFilter('translate')->getCallable()("Intl_NumberSymbolDecimal"));
        yield ",
        patternsCompactNumber: {
";
        // line 20
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(range(0, 15));
        foreach ($context['_seq'] as $context["_key"] => $context["i"]) {
            // line 21
            yield "            \"";
            yield \Piwik\piwik_escape_filter($this->env, (10 ** $context["i"]), "html", null, true);
            yield "000One\": ";
            yield json_encode($this->env->getFilter('translate')->getCallable()((("Intl_NumberFormatNumberCompact" . (10 ** $context["i"])) . "000One")));
            yield ",
            \"";
            // line 22
            yield \Piwik\piwik_escape_filter($this->env, (10 ** $context["i"]), "html", null, true);
            yield "000Other\": ";
            yield json_encode($this->env->getFilter('translate')->getCallable()((("Intl_NumberFormatNumberCompact" . (10 ** $context["i"])) . "000Other")));
            yield ",
";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['i'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 24
        yield "        },
        patternsCompactCurrency: {";
        // line 26
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(range(0, 15));
        foreach ($context['_seq'] as $context["_key"] => $context["i"]) {
            // line 27
            yield "            \"";
            yield \Piwik\piwik_escape_filter($this->env, (10 ** $context["i"]), "html", null, true);
            yield "000One\": ";
            yield json_encode($this->env->getFilter('translate')->getCallable()((("Intl_NumberFormatCurrencyCompact" . (10 ** $context["i"])) . "000One")));
            yield ",
            \"";
            // line 28
            yield \Piwik\piwik_escape_filter($this->env, (10 ** $context["i"]), "html", null, true);
            yield "000Other\": ";
            yield json_encode($this->env->getFilter('translate')->getCallable()((("Intl_NumberFormatCurrencyCompact" . (10 ** $context["i"])) . "000Other")));
            yield ",";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['i'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 30
        yield "        }
    };

    piwik.relativePluginWebDirs = ";
        // line 33
        yield json_encode((isset($context["relativePluginWebDirs"]) || array_key_exists("relativePluginWebDirs", $context) ? $context["relativePluginWebDirs"] : (function () { throw new RuntimeError('Variable "relativePluginWebDirs" does not exist.', 33, $this->source); })()));
        yield ";

    ";
        // line 35
        if ((isset($context["userLogin"]) || array_key_exists("userLogin", $context) ? $context["userLogin"] : (function () { throw new RuntimeError('Variable "userLogin" does not exist.', 35, $this->source); })())) {
            yield "piwik.userLogin = \"";
            yield \Piwik\piwik_escape_filter($this->env, \Piwik\piwik_escape_filter($this->env, (isset($context["userLogin"]) || array_key_exists("userLogin", $context) ? $context["userLogin"] : (function () { throw new RuntimeError('Variable "userLogin" does not exist.', 35, $this->source); })()), "js"), "html", null, true);
            yield "\";";
        }
        // line 36
        yield "
    ";
        // line 37
        if (array_key_exists("idSite", $context)) {
            yield "piwik.idSite = \"";
            yield \Piwik\piwik_escape_filter($this->env, (isset($context["idSite"]) || array_key_exists("idSite", $context) ? $context["idSite"] : (function () { throw new RuntimeError('Variable "idSite" does not exist.', 37, $this->source); })()), "html", null, true);
            yield "\";";
        }
        // line 38
        yield "
    piwik.requiresPasswordConfirmation = ";
        // line 39
        yield (((isset($context["userRequiresPasswordConfirmation"]) || array_key_exists("userRequiresPasswordConfirmation", $context) ? $context["userRequiresPasswordConfirmation"] : (function () { throw new RuntimeError('Variable "userRequiresPasswordConfirmation" does not exist.', 39, $this->source); })())) ? ("true") : ("false"));
        yield ";

    ";
        // line 41
        if (array_key_exists("siteName", $context)) {
            // line 42
            yield "    // NOTE: siteName is currently considered deprecated, use piwik.currentSiteName instead, which will not contain HTML entities
    piwik.siteName = \"";
            // line 43
            yield \Piwik\piwik_escape_filter($this->env, \Piwik\piwik_escape_filter($this->env, (isset($context["siteName"]) || array_key_exists("siteName", $context) ? $context["siteName"] : (function () { throw new RuntimeError('Variable "siteName" does not exist.', 43, $this->source); })()), "js"), "html", null, true);
            yield "\";
    ";
            // line 44
            if (array_key_exists("siteNameDecoded", $context)) {
                yield " // just to be safe
    piwik.currentSiteName = ";
                // line 45
                yield json_encode((isset($context["siteNameDecoded"]) || array_key_exists("siteNameDecoded", $context) ? $context["siteNameDecoded"] : (function () { throw new RuntimeError('Variable "siteNameDecoded" does not exist.', 45, $this->source); })()));
                yield ";";
            }
            // line 46
            yield "    ";
        }
        // line 47
        yield "
    ";
        // line 48
        if (array_key_exists("siteMainUrl", $context)) {
            yield "piwik.siteMainUrl = \"";
            yield \Piwik\piwik_escape_filter($this->env, \Piwik\piwik_escape_filter($this->env, (isset($context["siteMainUrl"]) || array_key_exists("siteMainUrl", $context) ? $context["siteMainUrl"] : (function () { throw new RuntimeError('Variable "siteMainUrl" does not exist.', 48, $this->source); })()), "js"), "html", null, true);
            yield "\";";
        }
        // line 49
        yield "
    ";
        // line 50
        if (array_key_exists("period", $context)) {
            yield "piwik.period = \"";
            yield \Piwik\piwik_escape_filter($this->env, (isset($context["period"]) || array_key_exists("period", $context) ? $context["period"] : (function () { throw new RuntimeError('Variable "period" does not exist.', 50, $this->source); })()), "html", null, true);
            yield "\";";
        }
        // line 51
        yield "
";
        // line 56
        yield "    piwik.currentDateString = \"";
        yield \Piwik\piwik_escape_filter($this->env, ((array_key_exists("date", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["date"]) || array_key_exists("date", $context) ? $context["date"] : (function () { throw new RuntimeError('Variable "date" does not exist.', 56, $this->source); })()), ((array_key_exists("endDate", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["endDate"]) || array_key_exists("endDate", $context) ? $context["endDate"] : (function () { throw new RuntimeError('Variable "endDate" does not exist.', 56, $this->source); })()), "")) : ("")))) : (((array_key_exists("endDate", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["endDate"]) || array_key_exists("endDate", $context) ? $context["endDate"] : (function () { throw new RuntimeError('Variable "endDate" does not exist.', 56, $this->source); })()), "")) : ("")))), "html", null, true);
        yield "\";
";
        // line 57
        if (array_key_exists("startDate", $context)) {
            // line 58
            yield "    piwik.startDateString = \"";
            yield \Piwik\piwik_escape_filter($this->env, (isset($context["startDate"]) || array_key_exists("startDate", $context) ? $context["startDate"] : (function () { throw new RuntimeError('Variable "startDate" does not exist.', 58, $this->source); })()), "html", null, true);
            yield "\";
    piwik.endDateString = \"";
            // line 59
            yield \Piwik\piwik_escape_filter($this->env, (isset($context["endDate"]) || array_key_exists("endDate", $context) ? $context["endDate"] : (function () { throw new RuntimeError('Variable "endDate" does not exist.', 59, $this->source); })()), "html", null, true);
            yield "\";
    piwik.minDateYear = ";
            // line 60
            yield \Piwik\piwik_escape_filter($this->env, (isset($context["minDateYear"]) || array_key_exists("minDateYear", $context) ? $context["minDateYear"] : (function () { throw new RuntimeError('Variable "minDateYear" does not exist.', 60, $this->source); })()), "html", null, true);
            yield ";
    piwik.minDateMonth = parseInt(\"";
            // line 61
            yield \Piwik\piwik_escape_filter($this->env, (isset($context["minDateMonth"]) || array_key_exists("minDateMonth", $context) ? $context["minDateMonth"] : (function () { throw new RuntimeError('Variable "minDateMonth" does not exist.', 61, $this->source); })()), "html", null, true);
            yield "\", 10);
    piwik.minDateDay = parseInt(\"";
            // line 62
            yield \Piwik\piwik_escape_filter($this->env, (isset($context["minDateDay"]) || array_key_exists("minDateDay", $context) ? $context["minDateDay"] : (function () { throw new RuntimeError('Variable "minDateDay" does not exist.', 62, $this->source); })()), "html", null, true);
            yield "\", 10);
    piwik.maxDateYear = ";
            // line 63
            yield \Piwik\piwik_escape_filter($this->env, (isset($context["maxDateYear"]) || array_key_exists("maxDateYear", $context) ? $context["maxDateYear"] : (function () { throw new RuntimeError('Variable "maxDateYear" does not exist.', 63, $this->source); })()), "html", null, true);
            yield ";
    piwik.maxDateMonth = parseInt(\"";
            // line 64
            yield \Piwik\piwik_escape_filter($this->env, (isset($context["maxDateMonth"]) || array_key_exists("maxDateMonth", $context) ? $context["maxDateMonth"] : (function () { throw new RuntimeError('Variable "maxDateMonth" does not exist.', 64, $this->source); })()), "html", null, true);
            yield "\", 10);
    piwik.maxDateDay = parseInt(\"";
            // line 65
            yield \Piwik\piwik_escape_filter($this->env, (isset($context["maxDateDay"]) || array_key_exists("maxDateDay", $context) ? $context["maxDateDay"] : (function () { throw new RuntimeError('Variable "maxDateDay" does not exist.', 65, $this->source); })()), "html", null, true);
            yield "\", 10);
";
        }
        // line 67
        yield "    ";
        if (array_key_exists("language", $context)) {
            yield "piwik.language = \"";
            yield \Piwik\piwik_escape_filter($this->env, (isset($context["language"]) || array_key_exists("language", $context) ? $context["language"] : (function () { throw new RuntimeError('Variable "language" does not exist.', 67, $this->source); })()), "html", null, true);
            yield "\";";
        }
        // line 68
        yield "
    piwik.hasSuperUserAccess = ";
        // line 69
        yield \Piwik\piwik_escape_filter($this->env, \Piwik\piwik_escape_filter($this->env, ((array_key_exists("hasSuperUserAccess", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["hasSuperUserAccess"]) || array_key_exists("hasSuperUserAccess", $context) ? $context["hasSuperUserAccess"] : (function () { throw new RuntimeError('Variable "hasSuperUserAccess" does not exist.', 69, $this->source); })()), 0)) : (0)), "js"), "html", null, true);
        yield ";
    piwik.userHasSomeAdminAccess = ";
        // line 70
        yield json_encode((isset($context["userHasSomeAdminAccess"]) || array_key_exists("userHasSomeAdminAccess", $context) ? $context["userHasSomeAdminAccess"] : (function () { throw new RuntimeError('Variable "userHasSomeAdminAccess" does not exist.', 70, $this->source); })()));
        yield ";
    piwik.userCapabilities = ";
        // line 71
        yield json_encode(((array_key_exists("userCapabilities", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["userCapabilities"]) || array_key_exists("userCapabilities", $context) ? $context["userCapabilities"] : (function () { throw new RuntimeError('Variable "userCapabilities" does not exist.', 71, $this->source); })()), [])) : ([])));
        yield ";
    piwik.config = {};
";
        // line 73
        if (array_key_exists("clientSideConfig", $context)) {
            // line 74
            yield "    piwik.config = ";
            yield json_encode((isset($context["clientSideConfig"]) || array_key_exists("clientSideConfig", $context) ? $context["clientSideConfig"] : (function () { throw new RuntimeError('Variable "clientSideConfig" does not exist.', 74, $this->source); })()));
            yield ";
";
        }
        // line 76
        yield "    Object.freeze(piwik.config); // disallow accidental changes
    piwik.shouldPropagateTokenAuth = ";
        // line 77
        yield json_encode((isset($context["shouldPropagateTokenAuth"]) || array_key_exists("shouldPropagateTokenAuth", $context) ? $context["shouldPropagateTokenAuth"] : (function () { throw new RuntimeError('Variable "shouldPropagateTokenAuth" does not exist.', 77, $this->source); })()));
        yield ";
    ";
        // line 78
        yield $this->env->getFunction('postEvent')->getCallable()("Template.jsGlobalVariables");
        yield "
</script>

";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "_jsGlobalVariables.twig";
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
        return array (  302 => 78,  298 => 77,  295 => 76,  289 => 74,  287 => 73,  282 => 71,  278 => 70,  274 => 69,  271 => 68,  264 => 67,  259 => 65,  255 => 64,  251 => 63,  247 => 62,  243 => 61,  239 => 60,  235 => 59,  230 => 58,  228 => 57,  223 => 56,  220 => 51,  214 => 50,  211 => 49,  205 => 48,  202 => 47,  199 => 46,  195 => 45,  191 => 44,  187 => 43,  184 => 42,  182 => 41,  177 => 39,  174 => 38,  168 => 37,  165 => 36,  159 => 35,  154 => 33,  149 => 30,  140 => 28,  133 => 27,  129 => 26,  126 => 24,  116 => 22,  109 => 21,  105 => 20,  100 => 18,  96 => 17,  92 => 16,  88 => 15,  84 => 14,  80 => 13,  76 => 12,  72 => 11,  68 => 9,  62 => 8,  58 => 7,  54 => 6,  50 => 5,  46 => 4,  42 => 3,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("<script type=\"text/javascript\">
    var piwik = {};
    piwik.pluginsToLoadOnDemand = {{ pluginsToLoadOnDemand|json_encode|raw }};
    piwik.token_auth = \"{{ token_auth }}\";
    piwik.piwik_url = \"{{ piwikUrl }}\";
    piwik.cacheBuster = \"{{ cacheBuster }}\";
    piwik.disableTrackingMatomoAppLinks = {{ disableTrackingMatomoAppLinks }};
    {% if timezoneOffset is defined %}piwik.timezoneOffset = {{ timezoneOffset }};{% endif %}

    piwik.numbers = {
        patternNumber: {{ 'Intl_NumberFormatNumber'|translate|json_encode|raw }},
        patternPercent: {{ 'Intl_NumberFormatPercent'|translate|json_encode|raw }},
        patternCurrency: {{ 'Intl_NumberFormatCurrency'|translate|json_encode|raw }},
        symbolPlus: {{ 'Intl_NumberSymbolPlus'|translate|json_encode|raw }},
        symbolMinus: {{ 'Intl_NumberSymbolMinus'|translate|json_encode|raw }},
        symbolPercent: {{ 'Intl_NumberSymbolPercent'|translate|json_encode|raw }},
        symbolGroup: {{ 'Intl_NumberSymbolGroup'|translate|json_encode|raw }},
        symbolDecimal: {{ 'Intl_NumberSymbolDecimal'|translate|json_encode|raw }},
        patternsCompactNumber: {
{% for i in 0..15 %}
            \"{{ 10**i }}000One\": {{ ('Intl_NumberFormatNumberCompact' ~ 10**i ~ '000One')|translate|json_encode|raw }},
            \"{{ 10**i }}000Other\": {{ ('Intl_NumberFormatNumberCompact' ~ 10**i ~ '000Other')|translate|json_encode|raw }},
{% endfor %}
        },
        patternsCompactCurrency: {
{%- for i in 0..15 %}
            \"{{ 10**i }}000One\": {{ ('Intl_NumberFormatCurrencyCompact' ~ 10**i ~ '000One')|translate|json_encode|raw }},
            \"{{ 10**i }}000Other\": {{ ('Intl_NumberFormatCurrencyCompact' ~ 10**i ~ '000Other')|translate|json_encode|raw }},
{%- endfor %}
        }
    };

    piwik.relativePluginWebDirs = {{ relativePluginWebDirs|json_encode|raw }};

    {% if userLogin %}piwik.userLogin = \"{{ userLogin|e('js')}}\";{% endif %}

    {% if idSite is defined %}piwik.idSite = \"{{ idSite }}\";{% endif %}

    piwik.requiresPasswordConfirmation = {{ userRequiresPasswordConfirmation ? 'true' : 'false' }};

    {% if siteName is defined %}
    // NOTE: siteName is currently considered deprecated, use piwik.currentSiteName instead, which will not contain HTML entities
    piwik.siteName = \"{{ siteName|e('js') }}\";
    {% if siteNameDecoded is defined %} // just to be safe
    piwik.currentSiteName = {{ siteNameDecoded|json_encode|raw }};{% endif %}
    {% endif %}

    {% if siteMainUrl is defined %}piwik.siteMainUrl = \"{{ siteMainUrl|e('js') }}\";{% endif %}

    {% if period is defined %}piwik.period = \"{{ period }}\";{% endif %}

{# piwik.currentDateString should not be used other than by the calendar Javascript
            (it is not set to the expected value when period=range)
        Use broadcast.getValueFromUrl('date') instead
#}
    piwik.currentDateString = \"{{ date|default(endDate|default('')) }}\";
{% if startDate is defined %}
    piwik.startDateString = \"{{ startDate }}\";
    piwik.endDateString = \"{{ endDate }}\";
    piwik.minDateYear = {{ minDateYear }};
    piwik.minDateMonth = parseInt(\"{{ minDateMonth }}\", 10);
    piwik.minDateDay = parseInt(\"{{ minDateDay }}\", 10);
    piwik.maxDateYear = {{ maxDateYear }};
    piwik.maxDateMonth = parseInt(\"{{ maxDateMonth }}\", 10);
    piwik.maxDateDay = parseInt(\"{{ maxDateDay }}\", 10);
{% endif %}
    {% if language is defined %}piwik.language = \"{{ language }}\";{% endif %}

    piwik.hasSuperUserAccess = {{ hasSuperUserAccess|default(0)|e('js')}};
    piwik.userHasSomeAdminAccess = {{ userHasSomeAdminAccess|json_encode|raw }};
    piwik.userCapabilities = {{ userCapabilities|default([])|json_encode|raw }};
    piwik.config = {};
{% if clientSideConfig is defined %}
    piwik.config = {{ clientSideConfig|json_encode|raw }};
{% endif %}
    Object.freeze(piwik.config); // disallow accidental changes
    piwik.shouldPropagateTokenAuth = {{ shouldPropagateTokenAuth|json_encode|raw }};
    {{ postEvent(\"Template.jsGlobalVariables\") }}
</script>

", "_jsGlobalVariables.twig", "C:\\xampp\\htdocs\\webTrafficAnalysis\\analytics\\plugins\\Morpheus\\templates\\_jsGlobalVariables.twig");
    }
}
