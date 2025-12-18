# 📐 User Management Module Architecture

## 🎯 模块架构说明 (Module Architecture)

本User Management模块遵循**模块化设计原则**，确保内外部访问分离：

### 📊 架构图 (Architecture Diagram)

```
User Management Module (Internal)          Other Modules (External)
          ↓                                          ↓
   UserController                             UserApiClient
          ↓                                          ↓
    UserService (Direct DB)                   HTTP Request
          ↓                                          ↓
     User Model                               /api/users/*
          ↓                                          ↓
      Database                              UserApiController
                                                     ↓
                                              UserService (Direct DB)
                                                     ↓
                                                User Model
                                                     ↓
                                                 Database
```

---

## 🔹 Internal Access (内部直接访问)

### 路径：UserController → UserService → User Model → Database

**使用场景：**
- User Management模块自己的网页界面
- 内部管理功能（CRUD操作）

**示例代码：**

```php
// UserController.php
class UserController extends Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(Request $request)
    {
        // 直接通过Service访问数据库
        $users = $this->userService->getFilteredUsers($request, 10);
        return view('users.index', compact('users'));
    }

    public function show($id)
    {
        // 所有查询都在Service里执行
        $user = $this->userService->getUserById($id);
        return view('users.show', compact('user'));
    }
}
```

```php
// UserService.php - 所有数据库查询在这里执行
class UserService
{
    public function getFilteredUsers(Request $request, int $perPage = 10)
    {
        // 查询逻辑在Service层
        $query = User::withTrashed()->where('role', '!=', 'Admin');

        if (Auth::user()->isStaff()) {
            $query->where('role', 'Student');
        }

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function getUserById($id)
    {
        return User::withTrashed()->find($id);
    }
}
```

---

## 🔸 External API Access (外部API访问)

### 路径：Other Modules → UserApiClient → HTTP → /api/users/* → UserApiController → UserService

**使用场景：**
- Borrowing模块需要验证用户信息
- Reservation模块需要检查用户状态
- 任何其他模块需要用户数据

**示例代码：**

```php
// BorrowingController.php (其他模块)
class BorrowingController extends Controller
{
    private UserApiClient $userApiClient;

    public function __construct(UserApiClient $userApiClient)
    {
        $this->userApiClient = $userApiClient;
    }

    public function store(Request $request)
    {
        $userId = $request->user_id;

        // ✅ 正确：通过API Client访问User数据
        $user = $this->userApiClient->getUser($userId);

        // ❌ 错误：不要直接访问UserService或User Model
        // $user = User::find($userId); // 违反模块边界！

        // 检查用户是否活跃
        if (!$this->userApiClient->isUserActive($userId)) {
            throw new \Exception('User is inactive');
        }

        // 创建借阅记录...
    }
}
```

```php
// UserApiClient.php - HTTP客户端
class UserApiClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('app.url') . '/api/users';
    }

    public function getUser(int $userId)
    {
        $response = Http::get("{$this->baseUrl}/{$userId}");
        $data = $response->json();
        return $data['data'];
    }

    public function isUserActive(int $userId): bool
    {
        $user = $this->getUser($userId);
        return $user['status'] === 'Active';
    }
}
```

```php
// UserApiController.php - REST API
class UserApiController extends Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function show($id): JsonResponse
    {
        // API Controller也使用Service访问数据库
        $user = $this->userService->getUserById($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }
}
```

---

## 📋 API Routes (API路由)

### routes/api.php

```php
Route::prefix('users')->group(function () {
    // GET /api/users - List all users
    Route::get('/', [UserApiController::class, 'index']);
    
    // GET /api/users/{id} - Get single user
    Route::get('/{id}', [UserApiController::class, 'show']);
    
    // POST /api/users - Create user
    Route::post('/', [UserApiController::class, 'store']);
    
    // PUT /api/users/{id} - Update user
    Route::put('/{id}', [UserApiController::class, 'update']);
    
    // DELETE /api/users/{id} - Delete user
    Route::delete('/{id}', [UserApiController::class, 'destroy']);
    
    // GET /api/users/role/{role} - Get users by role
    Route::get('/role/{role}', [UserApiController::class, 'getByRole']);
    
    // GET /api/users/search/query - Search users
    Route::get('/search/query', [UserApiController::class, 'search']);
    
    // GET /api/users/stats/overview - Get statistics
    Route::get('/stats/overview', [UserApiController::class, 'stats']);
});
```

---

## 🎯 核心原则 (Core Principles)

### 1. **所有查询在Service层执行**
   - ✅ Controller只负责接收请求和返回响应
   - ✅ Service负责所有数据库查询逻辑
   - ✅ Model只定义数据结构和关系

```php
// ✅ 正确
class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = $this->userService->getFilteredUsers($request, 10);
        return view('users.index', compact('users'));
    }
}

// ❌ 错误 - 不要在Controller里直接查询
class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::where('role', '!=', 'Admin')->paginate(10);
        return view('users.index', compact('users'));
    }
}
```

### 2. **模块边界清晰**
   - ✅ 内部访问：UserController → UserService
   - ✅ 外部访问：UserApiClient → HTTP API → UserApiController → UserService
   - ❌ 禁止：其他模块直接访问UserService或User Model

### 3. **依赖注入 (Dependency Injection)**
   - ✅ 通过构造函数注入Service
   - ✅ Laravel自动解析依赖

```php
class UserController extends Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
}
```

---

## 📁 文件结构 (File Structure)

```
app/
├── Http/
│   └── Controllers/
│       ├── UserController.php           # 内部访问控制器
│       └── Api/
│           └── UserApiController.php    # 外部API控制器
├── Services/
│   ├── UserService.php                  # 数据访问层 (所有查询在这里)
│   └── UserApiClient.php                # API客户端 (其他模块使用)
├── Models/
│   └── User.php                         # 数据模型
└── Factories/
    └── UserFactory.php                  # 工厂模式 (创建用户)
```

---

## 🔍 使用示例 (Usage Examples)

### Example 1: 在User Management内部获取用户列表

```php
// UserController.php
public function index(Request $request)
{
    // ✅ 使用UserService直接访问
    $users = $this->userService->getFilteredUsers($request, 10);
    return view('users.index', compact('users'));
}
```

### Example 2: Borrowing模块需要验证用户

```php
// BorrowingController.php
public function store(Request $request)
{
    $userId = $request->user_id;

    // ✅ 使用UserApiClient通过HTTP API访问
    $user = $this->userApiClient->getUser($userId);

    if (!$this->userApiClient->isUserActive($userId)) {
        throw new \Exception('User is inactive');
    }

    // 继续借阅逻辑...
}
```

### Example 3: Reservation模块检查用户角色

```php
// ReservationController.php
public function store(Request $request)
{
    $userId = Auth::id();

    // ✅ 使用UserApiClient验证用户角色
    if (!$this->userApiClient->userHasRole($userId, 'Student')) {
        throw new \Exception('Only students can make reservations');
    }

    // 创建预订...
}
```

---

## ✅ 验证清单 (Verification Checklist)

- [x] UserService存在且包含所有数据库查询方法
- [x] UserController使用UserService而不是直接查询数据库
- [x] UserApiController存在并提供REST API端点
- [x] UserApiClient存在供其他模块使用
- [x] API routes配置在routes/api.php
- [x] 所有查询逻辑在Service层执行
- [x] Controller只负责请求处理和响应返回

---

## 📊 与Book Module对比

| 组件 | Book Module | User Management Module |
|------|-------------|----------------------|
| **Internal Controller** | BookController | UserController |
| **Service (Direct DB)** | BookService | UserService |
| **Model** | Book | User |
| **API Controller** | BookApiController | UserApiController |
| **API Client** | BookApiClient | UserApiClient |
| **Factory Pattern** | ✅ | ✅ UserFactory |
| **Queries in Service** | ✅ | ✅ |

---

## 🎓 总结 (Summary)

User Management模块完全遵循与Book Module相同的架构模式：

1. **Internal Access (内部)**：UserController → UserService → User Model → Database
2. **External API (外部)**：Other Modules → UserApiClient → HTTP API → UserApiController → UserService
3. **所有查询在Service执行**：UserService包含所有数据库查询逻辑

这确保了：
- ✅ 模块边界清晰
- ✅ 代码可维护性高
- ✅ 易于测试
- ✅ 遵循SOLID原则
