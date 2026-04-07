<?php

namespace App\Collection\Infra\Doctrine\DQL;

// See https://www.doctrine-project.org/projects/doctrine-orm/en/current/cookbook/dql-user-defined-functions.html
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * AlbumSearchRankFunction ::= "ALBUM_SEARCH_RANK" "(" StringPrimary ")".
 */
final class AlbumSearchRank extends FunctionNode
{
    public Node $searchExpression;

    public function getSql(SqlWalker $sqlWalker): string
    {
        return "ts_rank(search_vector, websearch_to_tsquery(
            'simple', "
            .$this->searchExpression->dispatch($sqlWalker)
            .'))'
        ;
    }

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->searchExpression = $parser->StringPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }
}
