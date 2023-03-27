<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Mapper\Persister;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;

/**
 * Visit Expressions and generate SQL WHERE conditions from them.
 */
class SqlExpressionVisitor extends ExpressionVisitor
{
    public function __construct(private string $dql)
    {
    }

    /**
     * Converts a comparison expression into the target query language output.
     *
     * @return mixed
     */
    public function walkComparison(Comparison $comparison)
    {
        $field = $comparison->getField();
        $value = $comparison->getValue()->getValue();
        $pattern = '/(\w+\.'.preg_quote($field, '/').')/';
        preg_match($pattern, $this->dql, $matches);

        if (isset($matches[1])) {
            $field = $matches[1];
        }

        return sprintf(
            '%s%s\'%s\'',
            $field,
            $comparison->getOperator(),
            $value
        );
    }

    /**
     * Converts a composite expression into the target query language output.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        $expressionList = [];

        foreach ($expr->getExpressionList() as $child) {
            $expressionList[] = $this->dispatch($child);
        }

        switch ($expr->getType()) {
            case CompositeExpression::TYPE_AND:
                return '('.\implode(' AND ', $expressionList).')';

            case CompositeExpression::TYPE_OR:
                return '('.\implode(' OR ', $expressionList).')';

            default:
                throw new \RuntimeException('Unknown composite '.$expr->getType());
        }
    }

    /**
     * Converts a value expression into the target query language part.
     *
     * @return string
     */
    public function walkValue(Value $value)
    {
        return $value->getValue();
    }
}
