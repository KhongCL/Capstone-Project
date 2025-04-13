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

/* @CoreAdminHome/whatIsNew.twig */
class __TwigTemplate_440f7099c69b0daea7cb9b37a7b8a862 extends Template
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
        yield "<div vue-directive=\"CoreHome.ContentIntro\">
    <h2>";
        // line 2
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("CoreAdminHome_WhatIsNewTitle"), "html", null, true);
        yield "</h2>
</div>
<div class=\"whatisnew\">

    <div class=\"whatisnew-changelist\">

    ";
        // line 8
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable((isset($context["changes"]) || array_key_exists("changes", $context) ? $context["changes"] : (function () { throw new RuntimeError('Variable "changes" does not exist.', 8, $this->source); })()));
        foreach ($context['_seq'] as $context["_key"] => $context["change"]) {
            // line 9
            yield "
        <div class=\"card\">
            <div class=\"card-content\">
                <span style=\"float: left; font-size:32px; color:#3450A3; margin-right:10px\" class=\"icon-new_releases\"></span>
                ";
            // line 13
            if (((CoreExtension::getAttribute($this->env, $this->source, $context["change"], "plugin_name", [], "any", false, false, false, 13) == "CoreHome") || (CoreExtension::getAttribute($this->env, $this->source, $context["change"], "plugin_name", [], "any", false, false, false, 13) == "ProfessionalServices"))) {
                // line 14
                yield "                    <h2 class=\"card-title\">";
                yield \Piwik\piwik_escape_filter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["change"], "title", [], "any", false, false, false, 14), "html", null, true);
                yield "</h2>
                ";
            } else {
                // line 16
                yield "                    <h2 class=\"card-title\">";
                yield \Piwik\piwik_escape_filter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["change"], "plugin_name", [], "any", false, false, false, 16), "html", null, true);
                yield " - ";
                yield \Piwik\piwik_escape_filter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["change"], "title", [], "any", false, false, false, 16), "html", null, true);
                yield "</h2>
                ";
            }
            // line 18
            yield "                ";
            yield CoreExtension::getAttribute($this->env, $this->source, $context["change"], "description", [], "any", false, false, false, 18);
            yield "
                ";
            // line 19
            if (( !Twig\Extension\CoreExtension::testEmpty(CoreExtension::getAttribute($this->env, $this->source, $context["change"], "link", [], "any", false, false, false, 19)) &&  !Twig\Extension\CoreExtension::testEmpty(CoreExtension::getAttribute($this->env, $this->source, $context["change"], "link_name", [], "any", false, false, false, 19)))) {
                // line 20
                yield "                <p>
                    <br>
                    <a class=\"change-link\" href=\"";
                // line 22
                yield \Piwik\piwik_escape_filter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["change"], "link", [], "any", false, false, false, 22), "html", null, true);
                yield "\" target=\"_blank\" rel=\"noopener\">";
                yield \Piwik\piwik_escape_filter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["change"], "link_name", [], "any", false, false, false, 22), "html", null, true);
                yield "</a>
                </p>
                ";
            }
            // line 25
            yield "            </div>
        </div>

    ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['change'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 29
        yield "
    </div>

    <button href=\"javascript:void 0;\" class=\"btn whatisnew-btn whatisnew-remind-me-later\" onclick=\"Piwik_Popover.close()\"
       title=\"";
        // line 33
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("CoreAdminHome_WhatIsNewRemindMeLater"), "html_attr");
        yield "\">";
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("CoreAdminHome_WhatIsNewRemindMeLater"), "html_attr");
        yield "
    </button>

    <button href=\"javascript:void 0;\" class=\"btn whatisnew-btn whatisnew-do-not-show-again\" onclick=\"doNotShowAgain()\"
       title=\"";
        // line 37
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("CoreAdminHome_WhatIsNewDoNotShowAgain"), "html_attr");
        yield "\">";
        yield \Piwik\piwik_escape_filter($this->env, $this->env->getFilter('translate')->getCallable()("CoreAdminHome_WhatIsNewDoNotShowAgain"), "html_attr");
        yield "
    </button>

</div>

 <script>
     function doNotShowAgain() {
        var ajaxRequest = new ajaxHelper();
        ajaxRequest.addParams({
            module: 'API',
            method: 'CoreAdminHome.whatIsNewMarkAllChangesReadForCurrentUser',
            format: 'json'
        }, 'get');
        ajaxRequest.withTokenInUrl();
        ajaxRequest.setFormat('json');
        ajaxRequest.setCallback(function() {
            var link = document.querySelector('a[onclick*=\"CoreAdminHome&action=whatIsNew\"] > .badge-menu-item');
            if (link) {
                link.style.display = \"none\";
            }
            var icon = document.querySelector('a[onclick*=\"CoreAdminHome&action=whatIsNew\"] > .navbar-icon');
            if (icon) {
                icon.classList.remove('icon-notifications_on');
                icon.classList.add('icon-reporting-actions');
            }
            Piwik_Popover.close();
        });
        ajaxRequest.useCallbackInCaseOfError()
        ajaxRequest.send();
     }
</script>
";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "@CoreAdminHome/whatIsNew.twig";
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
        return array (  119 => 37,  110 => 33,  104 => 29,  95 => 25,  87 => 22,  83 => 20,  81 => 19,  76 => 18,  68 => 16,  62 => 14,  60 => 13,  54 => 9,  50 => 8,  41 => 2,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("<div vue-directive=\"CoreHome.ContentIntro\">
    <h2>{{ 'CoreAdminHome_WhatIsNewTitle'|translate }}</h2>
</div>
<div class=\"whatisnew\">

    <div class=\"whatisnew-changelist\">

    {% for change in changes %}

        <div class=\"card\">
            <div class=\"card-content\">
                <span style=\"float: left; font-size:32px; color:#3450A3; margin-right:10px\" class=\"icon-new_releases\"></span>
                {% if change.plugin_name == \"CoreHome\" or change.plugin_name == \"ProfessionalServices\"%}
                    <h2 class=\"card-title\">{{ change.title }}</h2>
                {% else %}
                    <h2 class=\"card-title\">{{ change.plugin_name }} - {{ change.title }}</h2>
                {% endif %}
                {{ change.description | raw }}
                {% if not change.link is empty and not change.link_name is empty %}
                <p>
                    <br>
                    <a class=\"change-link\" href=\"{{ change.link }}\" target=\"_blank\" rel=\"noopener\">{{ change.link_name }}</a>
                </p>
                {% endif %}
            </div>
        </div>

    {% endfor %}

    </div>

    <button href=\"javascript:void 0;\" class=\"btn whatisnew-btn whatisnew-remind-me-later\" onclick=\"Piwik_Popover.close()\"
       title=\"{{ 'CoreAdminHome_WhatIsNewRemindMeLater'|translate|e('html_attr') }}\">{{ 'CoreAdminHome_WhatIsNewRemindMeLater'|translate|e('html_attr') }}
    </button>

    <button href=\"javascript:void 0;\" class=\"btn whatisnew-btn whatisnew-do-not-show-again\" onclick=\"doNotShowAgain()\"
       title=\"{{ 'CoreAdminHome_WhatIsNewDoNotShowAgain'|translate|e('html_attr') }}\">{{ 'CoreAdminHome_WhatIsNewDoNotShowAgain'|translate|e('html_attr') }}
    </button>

</div>

 <script>
     function doNotShowAgain() {
        var ajaxRequest = new ajaxHelper();
        ajaxRequest.addParams({
            module: 'API',
            method: 'CoreAdminHome.whatIsNewMarkAllChangesReadForCurrentUser',
            format: 'json'
        }, 'get');
        ajaxRequest.withTokenInUrl();
        ajaxRequest.setFormat('json');
        ajaxRequest.setCallback(function() {
            var link = document.querySelector('a[onclick*=\"CoreAdminHome&action=whatIsNew\"] > .badge-menu-item');
            if (link) {
                link.style.display = \"none\";
            }
            var icon = document.querySelector('a[onclick*=\"CoreAdminHome&action=whatIsNew\"] > .navbar-icon');
            if (icon) {
                icon.classList.remove('icon-notifications_on');
                icon.classList.add('icon-reporting-actions');
            }
            Piwik_Popover.close();
        });
        ajaxRequest.useCallbackInCaseOfError()
        ajaxRequest.send();
     }
</script>
", "@CoreAdminHome/whatIsNew.twig", "C:\\xampp\\htdocs\\webTrafficAnalysis\\analytics\\plugins\\CoreAdminHome\\templates\\whatIsNew.twig");
    }
}
