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

/* @ScheduledReports/index.twig */
class __TwigTemplate_d28009190dfaa0021a84273e502fc017 extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'topcontrols' => [$this, 'block_topcontrols'],
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
            yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("ScheduledReports_PersonalEmailReports"), "html", null, true);
            return; yield '';
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 1
        $this->parent = $this->loadTemplate("admin.twig", "@ScheduledReports/index.twig", 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
    }

    // line 5
    public function block_topcontrols($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 6
        yield "    ";
        yield from         $this->loadTemplate("@CoreHome/_siteSelectHeader.twig", "@ScheduledReports/index.twig", 6)->unwrap()->yield($context);
        // line 7
        yield "    ";
        yield from         $this->loadTemplate("@CoreHome/_periodSelect.twig", "@ScheduledReports/index.twig", 7)->unwrap()->yield($context);
        return; yield '';
    }

    // line 10
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 11
        yield "
";
        // line 12
        $context["reportParametersScheduledReportsEvent"] = ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros, $blocks) {
            yield $this->env->getFunction('postEvent')->getCallable()("Template.reportParametersScheduledReports");
            return; yield '';
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 13
        yield "
<div
  vue-entry=\"ScheduledReports.ManageScheduledReport\"
  content-title=\"";
        // line 16
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("title", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["title"]) || array_key_exists("title", $context) ? $context["title"] : (function () { throw new RuntimeError('Variable "title" does not exist.', 16, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
  user-login=\"";
        // line 17
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("userLogin", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["userLogin"]) || array_key_exists("userLogin", $context) ? $context["userLogin"] : (function () { throw new RuntimeError('Variable "userLogin" does not exist.', 17, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
  login-module=\"";
        // line 18
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("loginModule", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["loginModule"]) || array_key_exists("loginModule", $context) ? $context["loginModule"] : (function () { throw new RuntimeError('Variable "loginModule" does not exist.', 18, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
  reports=\"";
        // line 19
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("reports", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["reports"]) || array_key_exists("reports", $context) ? $context["reports"] : (function () { throw new RuntimeError('Variable "reports" does not exist.', 19, $this->source); })()), [])) : ([]))), "html", null, true);
        yield "\"
  site-name=\"";
        // line 20
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("siteName", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["siteName"]) || array_key_exists("siteName", $context) ? $context["siteName"] : (function () { throw new RuntimeError('Variable "siteName" does not exist.', 20, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
  segment-editor-activated=\"";
        // line 21
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("segmentEditorActivated", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["segmentEditorActivated"]) || array_key_exists("segmentEditorActivated", $context) ? $context["segmentEditorActivated"] : (function () { throw new RuntimeError('Variable "segmentEditorActivated" does not exist.', 21, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
  saved-segments-by-id=\"";
        // line 22
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("savedSegmentsById", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["savedSegmentsById"]) || array_key_exists("savedSegmentsById", $context) ? $context["savedSegmentsById"] : (function () { throw new RuntimeError('Variable "savedSegmentsById" does not exist.', 22, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
  periods=\"";
        // line 23
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("periods", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["periods"]) || array_key_exists("periods", $context) ? $context["periods"] : (function () { throw new RuntimeError('Variable "periods" does not exist.', 23, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
  download-output-type=\"";
        // line 24
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("downloadOutputType", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["downloadOutputType"]) || array_key_exists("downloadOutputType", $context) ? $context["downloadOutputType"] : (function () { throw new RuntimeError('Variable "downloadOutputType" does not exist.', 24, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
  language=\"";
        // line 25
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("language", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["language"]) || array_key_exists("language", $context) ? $context["language"] : (function () { throw new RuntimeError('Variable "language" does not exist.', 25, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
  report-formats-by-report-type=\"";
        // line 26
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("reportFormatsByReportType", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["reportFormatsByReportType"]) || array_key_exists("reportFormatsByReportType", $context) ? $context["reportFormatsByReportType"] : (function () { throw new RuntimeError('Variable "reportFormatsByReportType" does not exist.', 26, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
  param-periods=\"";
        // line 27
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("paramPeriods", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["paramPeriods"]) || array_key_exists("paramPeriods", $context) ? $context["paramPeriods"] : (function () { throw new RuntimeError('Variable "paramPeriods" does not exist.', 27, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
  report-type-options=\"";
        // line 28
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("reportTypeOptions", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["reportTypeOptions"]) || array_key_exists("reportTypeOptions", $context) ? $context["reportTypeOptions"] : (function () { throw new RuntimeError('Variable "reportTypeOptions" does not exist.', 28, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
  report-formats-by-report-type-options=\"";
        // line 29
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("reportFormatsByReportTypeOptions", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["reportFormatsByReportTypeOptions"]) || array_key_exists("reportFormatsByReportTypeOptions", $context) ? $context["reportFormatsByReportTypeOptions"] : (function () { throw new RuntimeError('Variable "reportFormatsByReportTypeOptions" does not exist.', 29, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
  display-formats=\"";
        // line 30
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("displayFormats", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["displayFormats"]) || array_key_exists("displayFormats", $context) ? $context["displayFormats"] : (function () { throw new RuntimeError('Variable "displayFormats" does not exist.', 30, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
  reports-by-category-by-report-type=\"";
        // line 31
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("reportsByCategoryByReportType", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["reportsByCategoryByReportType"]) || array_key_exists("reportsByCategoryByReportType", $context) ? $context["reportsByCategoryByReportType"] : (function () { throw new RuntimeError('Variable "reportsByCategoryByReportType" does not exist.', 31, $this->source); })()), [])) : ([]))), "html", null, true);
        yield "\"
  allow-multiple-reports-by-report-type=\"";
        // line 32
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("allowMultipleReportsByReportType", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["allowMultipleReportsByReportType"]) || array_key_exists("allowMultipleReportsByReportType", $context) ? $context["allowMultipleReportsByReportType"] : (function () { throw new RuntimeError('Variable "allowMultipleReportsByReportType" does not exist.', 32, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
  count-websites=\"";
        // line 33
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("countWebsites", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["countWebsites"]) || array_key_exists("countWebsites", $context) ? $context["countWebsites"] : (function () { throw new RuntimeError('Variable "countWebsites" does not exist.', 33, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
  report-types=\"";
        // line 34
        yield \Piwik\piwik_escape_filter($this->env, json_encode(((array_key_exists("reportTypes", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["reportTypes"]) || array_key_exists("reportTypes", $context) ? $context["reportTypes"] : (function () { throw new RuntimeError('Variable "reportTypes" does not exist.', 34, $this->source); })()), null)) : (null))), "html", null, true);
        yield "\"
>
    <template v-slot:report-parameters>
        ";
        // line 37
        yield (isset($context["reportParametersScheduledReportsEvent"]) || array_key_exists("reportParametersScheduledReportsEvent", $context) ? $context["reportParametersScheduledReportsEvent"] : (function () { throw new RuntimeError('Variable "reportParametersScheduledReportsEvent" does not exist.', 37, $this->source); })());
        yield "
    </template>
</div>

<div class=\"ui-confirm\" id=\"confirm\">
    <h2>";
        // line 42
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("ScheduledReports_AreYouSureDeleteReport"), "html", null, true);
        yield "</h2>
    <input role=\"yes\" type=\"button\" value=\"";
        // line 43
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("General_Yes"), "html", null, true);
        yield "\"/>
    <input role=\"no\" type=\"button\" value=\"";
        // line 44
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("General_No"), "html", null, true);
        yield "\"/>
</div>

<script type=\"text/javascript\">
    var ReportPlugin = {};
    ReportPlugin.defaultPeriod = '";
        // line 49
        yield \Piwik\piwik_escape_filter($this->env, (isset($context["defaultPeriod"]) || array_key_exists("defaultPeriod", $context) ? $context["defaultPeriod"] : (function () { throw new RuntimeError('Variable "defaultPeriod" does not exist.', 49, $this->source); })()), "html", null, true);
        yield "';
    ReportPlugin.defaultHour = '";
        // line 50
        yield \Piwik\piwik_escape_filter($this->env, (isset($context["defaultHour"]) || array_key_exists("defaultHour", $context) ? $context["defaultHour"] : (function () { throw new RuntimeError('Variable "defaultHour" does not exist.', 50, $this->source); })()), "html", null, true);
        yield "';
    ReportPlugin.defaultReportType = '";
        // line 51
        yield \Piwik\piwik_escape_filter($this->env, (isset($context["defaultReportType"]) || array_key_exists("defaultReportType", $context) ? $context["defaultReportType"] : (function () { throw new RuntimeError('Variable "defaultReportType" does not exist.', 51, $this->source); })()), "html", null, true);
        yield "';
    ReportPlugin.defaultReportFormat = '";
        // line 52
        yield \Piwik\piwik_escape_filter($this->env, (isset($context["defaultReportFormat"]) || array_key_exists("defaultReportFormat", $context) ? $context["defaultReportFormat"] : (function () { throw new RuntimeError('Variable "defaultReportFormat" does not exist.', 52, $this->source); })()), "html", null, true);
        yield "';
    ReportPlugin.reportList = ";
        // line 53
        yield (isset($context["reportsJSON"]) || array_key_exists("reportsJSON", $context) ? $context["reportsJSON"] : (function () { throw new RuntimeError('Variable "reportsJSON" does not exist.', 53, $this->source); })());
        yield ";
    ReportPlugin.createReportString = \"";
        // line 54
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("ScheduledReports_CreateReport"), "html", null, true);
        yield "\";
    ReportPlugin.updateReportString = \"";
        // line 55
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("ScheduledReports_UpdateReport"), "html", null, true);
        yield "\";
    ReportPlugin.defaultEvolutionPeriodN = ";
        // line 56
        yield json_encode((isset($context["defaultEvolutionPeriodN"]) || array_key_exists("defaultEvolutionPeriodN", $context) ? $context["defaultEvolutionPeriodN"] : (function () { throw new RuntimeError('Variable "defaultEvolutionPeriodN" does not exist.', 56, $this->source); })()));
        yield ";
    ReportPlugin.periodTranslations = ";
        // line 57
        yield json_encode((isset($context["periodTranslations"]) || array_key_exists("periodTranslations", $context) ? $context["periodTranslations"] : (function () { throw new RuntimeError('Variable "periodTranslations" does not exist.', 57, $this->source); })()));
        yield ";
</script>
<style type=\"text/css\">
    .reportCategory {
        font-weight: bold;
        margin-bottom: 5px;
    }

    .entityAddContainer {
        position:relative;
    }

    .emailReports .top_controls {
        padding-bottom: 18px;
    }

</style>
";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "@ScheduledReports/index.twig";
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
        return array (  218 => 57,  214 => 56,  210 => 55,  206 => 54,  202 => 53,  198 => 52,  194 => 51,  190 => 50,  186 => 49,  178 => 44,  174 => 43,  170 => 42,  162 => 37,  156 => 34,  152 => 33,  148 => 32,  144 => 31,  140 => 30,  136 => 29,  132 => 28,  128 => 27,  124 => 26,  120 => 25,  116 => 24,  112 => 23,  108 => 22,  104 => 21,  100 => 20,  96 => 19,  92 => 18,  88 => 17,  84 => 16,  79 => 13,  74 => 12,  71 => 11,  67 => 10,  61 => 7,  58 => 6,  54 => 5,  49 => 1,  44 => 3,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("{% extends 'admin.twig' %}

{% set title %}{{ 'ScheduledReports_PersonalEmailReports'|translate }}{% endset %}

{% block topcontrols %}
    {% include \"@CoreHome/_siteSelectHeader.twig\" %}
    {% include \"@CoreHome/_periodSelect.twig\" %}
{% endblock %}

{% block content %}

{% set reportParametersScheduledReportsEvent %}{{ postEvent('Template.reportParametersScheduledReports') }}{% endset %}

<div
  vue-entry=\"ScheduledReports.ManageScheduledReport\"
  content-title=\"{{ title|default(null)|json_encode }}\"
  user-login=\"{{ userLogin|default(null)|json_encode }}\"
  login-module=\"{{ loginModule|default(null)|json_encode }}\"
  reports=\"{{ reports|default([])|json_encode }}\"
  site-name=\"{{ siteName|default(null)|json_encode }}\"
  segment-editor-activated=\"{{ segmentEditorActivated|default(null)|json_encode }}\"
  saved-segments-by-id=\"{{ savedSegmentsById|default(null)|json_encode }}\"
  periods=\"{{ periods|default(null)|json_encode }}\"
  download-output-type=\"{{ downloadOutputType|default(null)|json_encode }}\"
  language=\"{{ language|default(null)|json_encode }}\"
  report-formats-by-report-type=\"{{ reportFormatsByReportType|default(null)|json_encode }}\"
  param-periods=\"{{ paramPeriods|default(null)|json_encode }}\"
  report-type-options=\"{{ reportTypeOptions|default(null)|json_encode }}\"
  report-formats-by-report-type-options=\"{{ reportFormatsByReportTypeOptions|default(null)|json_encode }}\"
  display-formats=\"{{ displayFormats|default(null)|json_encode }}\"
  reports-by-category-by-report-type=\"{{ reportsByCategoryByReportType|default({})|json_encode }}\"
  allow-multiple-reports-by-report-type=\"{{ allowMultipleReportsByReportType|default(null)|json_encode }}\"
  count-websites=\"{{ countWebsites|default(null)|json_encode }}\"
  report-types=\"{{ reportTypes|default(null)|json_encode }}\"
>
    <template v-slot:report-parameters>
        {{ reportParametersScheduledReportsEvent|raw }}
    </template>
</div>

<div class=\"ui-confirm\" id=\"confirm\">
    <h2>{{ 'ScheduledReports_AreYouSureDeleteReport'|translate }}</h2>
    <input role=\"yes\" type=\"button\" value=\"{{ 'General_Yes'|translate }}\"/>
    <input role=\"no\" type=\"button\" value=\"{{ 'General_No'|translate }}\"/>
</div>

<script type=\"text/javascript\">
    var ReportPlugin = {};
    ReportPlugin.defaultPeriod = '{{ defaultPeriod }}';
    ReportPlugin.defaultHour = '{{ defaultHour }}';
    ReportPlugin.defaultReportType = '{{ defaultReportType }}';
    ReportPlugin.defaultReportFormat = '{{ defaultReportFormat }}';
    ReportPlugin.reportList = {{ reportsJSON | raw }};
    ReportPlugin.createReportString = \"{{ 'ScheduledReports_CreateReport'|translate }}\";
    ReportPlugin.updateReportString = \"{{ 'ScheduledReports_UpdateReport'|translate }}\";
    ReportPlugin.defaultEvolutionPeriodN = {{ defaultEvolutionPeriodN|json_encode|raw }};
    ReportPlugin.periodTranslations = {{ periodTranslations|json_encode|raw }};
</script>
<style type=\"text/css\">
    .reportCategory {
        font-weight: bold;
        margin-bottom: 5px;
    }

    .entityAddContainer {
        position:relative;
    }

    .emailReports .top_controls {
        padding-bottom: 18px;
    }

</style>
{% endblock %}
", "@ScheduledReports/index.twig", "C:\\xampp\\htdocs\\webTrafficAnalysis\\analytics\\plugins\\ScheduledReports\\templates\\index.twig");
    }
}
