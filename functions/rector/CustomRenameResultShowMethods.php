<?php

declare(strict_types=1);

namespace Rector\Custom;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use Rector\PhpParser\Node\Value\ValueResolver;
use Rector\Rector\AbstractRector;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;

final class CustomRenameResultShowMethods extends AbstractRector
{
    public function __construct(
        private readonly ValueResolver $valueResolver
    ) {}

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Refactor $Result->show*() calls into $Result->warn*() and $Result->die*() calls.',
            [
                new CodeSample(
                    '$this->Result->show($a, $b);',
                    '$this->Result->warn($a, $b);'
                ),
                new CodeSample(
                    '$this->Result->show($a, $b, false);',
                    '$this->Result->warn($a, $b);'
                ),
                new CodeSample(
                    '$this->Result->show($a, $b, true);',
                    '$this->Result->die($a, $b);'
                ),
                new CodeSample(
                    '$Result->show_cli("a");',
                    '$Result->warn(false, "a");'
                ),
                new CodeSample(
                    '$Result->show_cli("a", false);',
                    '$Result->warn(false, "a");'
                ),
                new CodeSample(
                    '$Result->show_cli("a", true);',
                    '$Result->die(false, "a");'
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    /**
     * @param  MethodCall  $node
     */
    public function refactor(Node $node): ?Node
    {
        // Check for variable or object property named $Result
        if (! $node->var instanceof Variable && ! $node->var instanceof PropertyFetch) {
            return null;
        }

        // PHP variables are case-sensitive, match only "Result"
        if ($this->getName($node->var) !== 'Result') {
            return null;
        }

        if ($this->isName($node->name, 'show')) {
            return $this->refactor_show($node, 2);
        }

        if ($this->isName($node->name, 'show_cli')) {
            $node = $this->refactor_show($node, 1);
            if ($node instanceof MethodCall) {
                // Result::show_cmd(string $message, bool $die = false) is an alias for Result::show(false, string $message, bool $die = false)
                // Insert false before the $message argument
                $falseNode = new ConstFetch(new Name('false'));
                array_unshift($node->args, new Arg($falseNode));
            }

            return $node;
        }

        return null;
    }

    /**
     * @param  MethodCall  $node
     */
    private function refactor_show(Node $node, int $die_arg_position): ?Node
    {
        $args = $node->getArgs();

        if (count($args) < $die_arg_position) {
            return null;
        }

        // Check for named arguments - abort if found
        foreach ($args as $arg) {
            if ($arg instanceof Arg && $arg->name instanceof Identifier) {
                return null;
            }
        }

        $new_method_name = 'warn';

        if (isset($args[$die_arg_position])) {
            $die_arg = $args[$die_arg_position]->value;

            if ($this->valueResolver->isTrue($die_arg)) {
                $new_method_name = 'die';
            } elseif (! $this->valueResolver->isFalse($die_arg)) {
                // If it's not a literal true/false, abort
                return null;
            }

            unset($node->args[$die_arg_position]);
        }

        // Rename method
        $node->name = new Identifier($new_method_name);

        return $node;
    }
}
