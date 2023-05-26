<?php
declare (strict_types = 1);
namespace YeAPF\ORM;


class EyeShotRelation extends \YeAPF\SanitizedKeyData{
  private $relations  = [];
  private $SQLCommand = '';

  private function buildSQLCommand() {
    $joinTypes = [
      YeAPF_INNER_JOIN => 'INNER JOIN',
      YeAPF_LEFT_JOIN  => 'LEFT JOIN',
      YeAPF_RIGHT_JOIN => 'RIGHT JOIN',
      YeAPF_FULL_JOIN  => 'FULL JOIN',
    ];

    $comparisonOperators = [
      YeAPF_EQUALS                => '=',
      YeAPF_NOT_EQUALS            => '!=',
      YeAPF_GREATER_THAN          => '>',
      YeAPF_GREATER_THAN_OR_EQUAL => '>=',
      YeAPF_LESS_THAN             => '<',
      YeAPF_LESS_THAN_OR_EQUAL    => '<=',
      YeAPF_IN                    => 'IN',
      YeAPF_NOT_IN                => 'NOT IN',
    ];

    $logicalOperators = [
      YeAPF_AND => 'AND',
      YeAPF_OR  => 'OR',
      YeAPF_NOT => 'NOT',
    ];

    $sqlCommand = '';

    foreach ($this->relations as $index => $relation) {
      $leftFieldName   = $relation['leftFieldName'];
      $operator        = $relation['operator'];
      $rightFieldName  = $relation['rightFieldName'];
      $logicalOperator = $relation['logicalOperator'];

      // Handle join type
      if ($index === 0) {
        // The first relation does not require a join type
        $sqlCommand .= sprintf('%s', $leftFieldName);
      } else {
        $joinType = $joinTypes[$operator] ?? '';
        $sqlCommand .= sprintf(' %s %s', $joinType, $leftFieldName);
      }

      // Handle comparison operator
      $comparisonOperator = $comparisonOperators[$operator] ?? '';
      $sqlCommand .= sprintf(' %s %s', $comparisonOperator, $rightFieldName);

      // Handle logical operator
      if ($logicalOperator !== null) {
        $logicalOperator = $logicalOperators[$logicalOperator] ?? '';
        $sqlCommand .= sprintf(' %s', $logicalOperator);
      }
    }

    $this->SQLCommand = $sqlCommand;
  }

  public function __construct(...$params) {
    // Check if the number of parameters is valid
    if (count($params) < 3 || count($params) % 4 !== 1) {
      throw new \YeAPF\YeAPFException('Invalid number of parameters', YeAPF_INVALID_NUMBER_OF_PARAMETERS);
    }

    for ($i = 0; $i < count($params); $i += 4) {
      $leftFieldName   = $params[$i];
      $operator        = $params[$i + 1];
      $rightFieldName  = $params[$i + 2];
      $logicalOperator = $params[$i + 3] ?? null;

      // Create the relation array
      $relation = [
        'leftFieldName'   => $leftFieldName,
        'operator'        => $operator,
        'rightFieldName'  => $rightFieldName,
        'logicalOperator' => $logicalOperator,
      ];

      // Add the relation to the array
      $this->relations[] = $relation;
    }

    // Build SQL command
    $this->buildSQLCommand();
  }

}

trait EyeShot {
  private static  ? iCollection $mainCollection = null;
  private static  ? EyeShotRelation $relation   = null;
  private static mixed $relatedCollections      = [];

  public static function setMainCollection(iCollection $collection) {
    if (null == self::$collection) {
      self::$mainCollection = $collection;
    } else {
      throw new \YeAPF\YeAPFException("Error Processing Request", YeAPF_MAIN_COLLECTION_ALREADY_DEFINED);
    }

  }

  public static function addRelatedCollection(
    int $joinType = YeAPF_INNER_JOIN,
    iCollection $collection,
    EyeShotRelation $relation
  ) {

  }
}