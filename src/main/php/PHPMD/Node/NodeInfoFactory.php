<?php

namespace PHPMD\Node;

use PDepend\Source\AST\ASTNode;
use PHPMD\AbstractNode as PHPMDAbstractNode;

class NodeInfoFactory
{
    /**
     * @param PHPMDAbstractNode<ASTNode> $node
     * @return NodeInfo
     */
    public static function fromNode(PHPMDAbstractNode $node)
    {
        $className = null;
        $methodName = null;
        $functionName = null;

        if ($node instanceof AbstractTypeNode) {
            $className = $node->getName();
        } elseif ($node instanceof MethodNode) {
            $className = $node->getParentName();
            $methodName = $node->getName();
        } elseif ($node instanceof FunctionNode) {
            $functionName = $node->getName();
        }

        return new NodeInfo(
            $node->getFileName(),
            $node->getNamespaceName(),
            $className,
            $methodName,
            $functionName,
            $node->getBeginLine(),
            $node->getEndLine()
        );
    }
}
