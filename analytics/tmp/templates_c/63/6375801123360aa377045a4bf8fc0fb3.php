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

/* @TagManager/trackingCodeReact.twig */
class __TwigTemplate_abca14865ce7fd4b7047f544deaa5d5a extends Template
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

";
        // line 3
        if ((isset($context["wasDetected"]) || array_key_exists("wasDetected", $context) ? $context["wasDetected"] : (function () { throw new RuntimeError('Variable "wasDetected" does not exist.', 3, $this->source); })())) {
            // line 4
            yield "<div class=\"system notification notification-info\">
    ";
            // line 5
            yield $this->env->getFilter('translate')->getCallable()("SitesManager_ReactDetected", $this->env->getFunction('externallink')->getCallable()("https://matomo.org/guide/tag-manager/"), "</a>", $this->env->getFunction('externallink')->getCallable()("https://matomo.org/faq/new-to-piwik/how-do-i-start-tracking-data-with-matomo-on-websites-that-use-react/"), "</a>");
            // line 10
            yield "
</div>
";
        }
        // line 13
        yield "
<p>";
        // line 14
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("TagManager_SiteWithoutDataReactIntro"), "html", null, true);
        yield "</p>
<br>
<p>";
        // line 16
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("SitesManager_SiteWithoutDataCloudflareFollowStepsIntro"), "html", null, true);
        yield "</p>
<div
        vue-entry=\"TagManager.TrackingSPAPage\"
        show-container-row=\"";
        // line 19
        yield \Piwik\piwik_escape_filter($this->env, json_encode((isset($context["showContainerRow"]) || array_key_exists("showContainerRow", $context) ? $context["showContainerRow"] : (function () { throw new RuntimeError('Variable "showContainerRow" does not exist.', 19, $this->source); })())), "html", null, true);
        yield "\"
        js-framework=\"react\"
>
</div>
<br>
<p>";
        // line 24
        yield $this->env->getFilter('translate')->getCallable()("TagManager_SiteWithoutDataReactFollowStepCompleted", "<strong>", "</strong>");
        yield "</p>";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "@TagManager/trackingCodeReact.twig";
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
        return array (  78 => 24,  70 => 19,  64 => 16,  59 => 14,  56 => 13,  51 => 10,  49 => 5,  46 => 4,  44 => 3,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("<h2>{{ 'SitesManager_StepByStepGuide'|translate }}</h2>

{% if wasDetected %}
<div class=\"system notification notification-info\">
    {{ 'SitesManager_ReactDetected'|translate(
        externallink('https://matomo.org/guide/tag-manager/'),
        '</a>',
        externallink('https://matomo.org/faq/new-to-piwik/how-do-i-start-tracking-data-with-matomo-on-websites-that-use-react/'),
        '</a>'
    )|raw }}
</div>
{% endif %}

<p>{{'TagManager_SiteWithoutDataReactIntro'|translate}}</p>
<br>
<p>{{'SitesManager_SiteWithoutDataCloudflareFollowStepsIntro'|translate}}</p>
<div
        vue-entry=\"TagManager.TrackingSPAPage\"
        show-container-row=\"{{ showContainerRow|json_encode }}\"
        js-framework=\"react\"
>
</div>
<br>
<p>{{ 'TagManager_SiteWithoutDataReactFollowStepCompleted'|translate('<strong>', '</strong>')|raw }}</p>", "@TagManager/trackingCodeReact.twig", "C:\\xampp\\htdocs\\webTrafficAnalysis\\analytics\\plugins\\TagManager\\templates\\trackingCodeReact.twig");
    }
}
