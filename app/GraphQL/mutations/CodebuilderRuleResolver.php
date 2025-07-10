<?php

namespace App\GraphQL\Mutations;

use App\Models\CodebuilderRule;

class CodebuilderRuleResolver
{
    public function create($_, array $args)
    {
        $input = $args['input'];

        return CodebuilderRule::create([
            'version_id' => $input['version_id'],
            'rule' => json_encode($input['rule']),
        ]);
    }

    public function update($_, array $args)
    {
        $rule = CodebuilderRule::findOrFail($args['id']);
        $rule->update([
            'rule' => json_encode($args['input']['rule']),
        ]);

        return $rule;
    }
}
