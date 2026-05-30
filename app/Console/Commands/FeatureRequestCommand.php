<?php

namespace App\Console\Commands;

use App\Support\FeatureCommandSupport;
use Illuminate\Console\Command;

class FeatureRequestCommand extends Command
{
    protected $signature = 'feature:request
                            {feature : The feature name}
                            {name : The request name}';

    protected $description = 'Create a form request inside a feature module';

    public function handle(FeatureCommandSupport $features): int
    {
        $featureName = $features->normalizeFeatureName((string) $this->argument('feature'));
        $requestName = $features->qualifyClassName((string) $this->argument('name'), 'Request');

        if (! $features->featureExists($featureName)) {
            $this->error("Feature {$featureName} does not exist. Run feature:create first.");

            return self::FAILURE;
        }

        $path = $features->featureFilePath($featureName, 'Requests', "{$requestName}.php");

        if (! $features->writeFile($path, $this->stub($featureName, $requestName))) {
            $this->error("Request {$requestName} already exists in feature {$featureName}.");

            return self::FAILURE;
        }

        $this->info("Request {$requestName} created successfully in feature {$featureName}.");
        $this->line('  - File: '.$features->displayPath($path));

        return self::SUCCESS;
    }

    protected function stub(string $featureName, string $requestName): string
    {
        return str_replace(
            ['{{ featureName }}', '{{ requestName }}'],
            [$featureName, $requestName],
            <<<'PHP'
<?php

namespace App\Features\{{ featureName }}\Requests;

use App\Exceptions\ApiException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class {{ requestName }} extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ApiException($validator->errors()->first(), 422);
    }
}
PHP
        );
    }
}
