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

/* @TagManager/trackingSPA.twig */
class __TwigTemplate_d5ddc4df9719b5270d7735a1c6dafe84 extends Template
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
        yield "<h2>";
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("SitesManager_StepByStepGuide"), "html", null, true);
        yield "</h2>

<p>";
        // line 3
        yield $this->env->getFilter('translate')->getCallable()("SitesManager_SiteWithoutDataSPADescription", $this->env->getFunction('externallink')->getCallable()("https://matomo.org/guide/tag-manager/"), "</a>", $this->env->getFunction('externallink')->getCallable()("https://developer.matomo.org/guides/spa-tracking"), "</a>");
        yield "</p>
<br>
<p>";
        // line 5
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("SitesManager_SiteWithoutDataCloudflareFollowStepsIntro"), "html", null, true);
        yield "</p>
<div
    vue-entry=\"TagManager.TrackingSPAPage\"
    show-container-row=\"";
        // line 8
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["showContainerRow"]) || array_key_exists("showContainerRow", $context) ? $context["showContainerRow"] : (function () { throw new RuntimeError('Variable "showContainerRow" does not exist.', 8, $this->source); })())), "html", null, true);
        yield "\"
>
</div>
<br>
<p>";
        // line 12
        yield $this->env->getFilter('translate')->getCallable()("SitesManager_SiteWithoutDataSPAFollowStepCompleted", "<strong>", "</strong>");
        yield "</p>";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "@TagManager/trackingSPA.twig";
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
        return array (  62 => 12,  55 => 8,  49 => 5,  44 => 3,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("<h2>{{ 'SitesManager_StepByStepGuide'|translate }}</h2>

<p>{{ 'SitesManager_SiteWithoutDataSPADescription'|translate(externallink('https://matomo.org/guide/tag-manager/'), '</a>', externallink('https://developer.matomo.org/guides/spa-tracking'), '</a>')|raw }}</p>
<br>
<p>{{ 'SitesManager_SiteWithoutDataCloudflareFollowStepsIntro'|translate }}</p>
<div
    vue-entry=\"TagManager.TrackingSPAPage\"
    show-container-row=\"{{ showContainerRow|json_encode }}\"
>
</div>
<br>
<p>{{ 'SitesManager_SiteWithoutDataSPAFollowStepCompleted'|translate('<strong>','</strong>')|raw }}</p>", "@TagManager/trackingSPA.twig", "C:\\xampp\\htdocs\\webTrafficAnalysis\\analytics\\plugins\\TagManager\\templates\\trackingSPA.twig");
    }
}
