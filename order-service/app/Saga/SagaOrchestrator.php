<?php

namespace App\Saga;

use Illuminate\Support\Facades\Log;

/**
 * Saga Orchestrator for distributed transactions.
 * 
 * Executes a sequence of steps. On failure, executes compensating transactions (rollbacks)
 * in reverse order for all previously completed steps.
 */
class SagaOrchestrator
{
    /** @var SagaStep[] */
    private array $steps = [];
    private array $completedSteps = [];
    private array $context = [];

    public function __construct(private readonly string $sagaId = '')
    {
        $this->context['saga_id'] = $sagaId ?: uniqid('saga_', true);
    }

    public function addStep(SagaStep $step): static
    {
        $this->steps[] = $step;
        return $this;
    }

    public function setContext(string $key, mixed $value): static
    {
        $this->context[$key] = $value;
        return $this;
    }

    public function getContext(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }

    /**
     * Execute all saga steps. On failure, run compensating transactions.
     */
    public function execute(): SagaResult
    {
        $sagaId = $this->context['saga_id'];
        Log::info("Saga [{$sagaId}]: Starting execution", ['steps' => count($this->steps)]);

        foreach ($this->steps as $index => $step) {
            try {
                Log::info("Saga [{$sagaId}]: Executing step", ['step' => $step->getName(), 'index' => $index]);
                $result = $step->execute($this->context);
                $this->context = array_merge($this->context, $result ?? []);
                $this->completedSteps[] = $step;
                Log::info("Saga [{$sagaId}]: Step completed", ['step' => $step->getName()]);
            } catch (\Throwable $e) {
                Log::error("Saga [{$sagaId}]: Step failed", [
                    'step' => $step->getName(),
                    'error' => $e->getMessage(),
                ]);
                $this->compensate($sagaId, $e->getMessage());
                return SagaResult::failure($e->getMessage(), $this->context);
            }
        }

        Log::info("Saga [{$sagaId}]: All steps completed successfully");
        return SagaResult::success($this->context);
    }

    /**
     * Run compensating transactions in reverse order.
     */
    private function compensate(string $sagaId, string $reason): void
    {
        Log::info("Saga [{$sagaId}]: Starting compensation", ['reason' => $reason, 'steps_to_rollback' => count($this->completedSteps)]);

        foreach (array_reverse($this->completedSteps) as $step) {
            try {
                Log::info("Saga [{$sagaId}]: Compensating step", ['step' => $step->getName()]);
                $step->compensate($this->context);
                Log::info("Saga [{$sagaId}]: Step compensated", ['step' => $step->getName()]);
            } catch (\Throwable $e) {
                Log::critical("Saga [{$sagaId}]: Compensation failed! Manual intervention required.", [
                    'step' => $step->getName(),
                    'error' => $e->getMessage(),
                ]);
                // Log but continue compensating other steps
            }
        }
    }
}
