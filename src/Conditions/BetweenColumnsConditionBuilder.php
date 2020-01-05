<?php
declare(strict_types=1);

namespace Yiisoft\Db\Conditions;

use Yiisoft\Db\ExpressionBuilderInterface;
use Yiisoft\Db\ExpressionBuilderTrait;
use Yiisoft\Db\ExpressionInterface;
use Yiisoft\Db\Query;

/**
 * Class BetweenColumnsConditionBuilder builds objects of {@see BetweenColumnsCondition}.
 */
class BetweenColumnsConditionBuilder implements ExpressionBuilderInterface
{
    use ExpressionBuilderTrait;

    /**
     * Method builds the raw SQL from the $expression that will not be additionally
     * escaped or quoted.
     *
     * @param ExpressionInterface|BetweenColumnsCondition $expression the expression to be built.
     * @param array $params the binding parameters.
     *
     * @return string the raw SQL that will not be additionally escaped or quoted.
     */
    public function build(ExpressionInterface $expression, array &$params = [])
    {
        $operator = $expression->getOperator();

        $startColumn = $this->escapeColumnName($expression->getIntervalStartColumn(), $params);
        $endColumn = $this->escapeColumnName($expression->getIntervalEndColumn(), $params);
        $value = $this->createPlaceholder($expression->getValue(), $params);

        return "$value $operator $startColumn AND $endColumn";
    }

    /**
     * Prepares column name to be used in SQL statement.
     *
     * @param Query|ExpressionInterface|string $columnName
     * @param array $params the binding parameters.
     *
     * @return string
     */
    protected function escapeColumnName($columnName, &$params = [])
    {
        if ($columnName instanceof Query) {
            [$sql, $params] = $this->queryBuilder->build($columnName, $params);

            return "($sql)";
        } elseif ($columnName instanceof ExpressionInterface) {
            return $this->queryBuilder->buildExpression($columnName, $params);
        } elseif (strpos($columnName, '(') === false) {
            return $this->queryBuilder->db->quoteColumnName($columnName);
        }

        return $columnName;
    }

    /**
     * Attaches $value to $params array and returns placeholder.
     *
     * @param mixed $value
     * @param array $params passed by reference
     *
     * @return string
     */
    protected function createPlaceholder($value, &$params)
    {
        if ($value instanceof ExpressionInterface) {
            return $this->queryBuilder->buildExpression($value, $params);
        }

        return $this->queryBuilder->bindParam($value, $params);
    }
}
