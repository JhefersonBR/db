<?php
declare(strict_types=1);

namespace Yiisoft\Db;

/**
 * CheckConstraint represents the metadata of a table `CHECK` constraint.
 */
class CheckConstraint extends Constraint
{
    /**
     * @var string the SQL of the `CHECK` constraint.
     */
    public $expression;
}
