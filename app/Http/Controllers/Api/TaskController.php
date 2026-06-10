<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Application\Tasks\Actions\CreateTask;
use App\Application\Tasks\Actions\UpdateTask;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\IndexTaskRequest;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Repositories\TaskRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    private const int DEFAULT_PER_PAGE = 15;

    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private CreateTask $createTask,
        private UpdateTask $updateTask,
    ) {}

    public function index(IndexTaskRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Task::class);

        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? self::DEFAULT_PER_PAGE);

        $tasks = $this->taskRepository->paginate(
            $validated['status'] ?? null,
            $perPage,
            (int) $request->user()->id
        );

        return TaskResource::collection($tasks);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        Gate::authorize('create', Task::class);

        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $task = $this->createTask->handle($data);

        return new TaskResource($task)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $taskId): TaskResource
    {
        $task = $this->taskRepository->findOrFail($taskId);

        Gate::authorize('view', $task);

        return new TaskResource($task);
    }

    public function update(UpdateTaskRequest $request, int $taskId): TaskResource
    {
        $task = $this->taskRepository->findOrFail($taskId);

        Gate::authorize('update', $task);

        $task = $this->updateTask->handle(
            $task,
            $request->validated(),
            (int) $request->user()->id
        );

        return new TaskResource($task);
    }

    public function destroy(int $taskId): Response
    {
        $task = $this->taskRepository->findOrFail($taskId);

        Gate::authorize('delete', $task);

        $this->taskRepository->delete($task);

        return response()->noContent();
    }
}
