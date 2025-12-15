<?php

namespace App\Http\Controllers;

use App\Factories\UserFactory;
use App\Models\User;
use App\Services\InputValidationService;
use App\Services\AccessControlService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

/**
 * User Management Controller
 * Handles all user management operations including CRUD
 */
class UserController extends Controller
{
    /**
     * Display a listing of all users (Staff and Admin)
     * Admin is not displayed in the list (super root only manages others)
     */
    public function index(Request $request)
    {
        try {
            // Access Control: Only Staff and Admin can view all users
            AccessControlService::authorize('view', 'all_users');

            // Build query
            $query = User::withTrashed()
                ->where('role', '!=', 'Admin');

            // Staff can only view Students
            if (Auth::user()->isStaff()) {
                $query->where('role', 'Student');
            }

            // Apply search filter
            if ($request->filled('q')) {
                $search = $request->q;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            // Apply role filter
            if ($request->filled('role')) {
                $query->where('role', $request->role);
            }

            // Apply status filter
            if ($request->filled('status')) {
                if ($request->status === 'Active') {
                    $query->whereNull('deleted_at');
                } else {
                    $query->whereNotNull('deleted_at');
                }
            }

            // Apply sorting
            $sortBy = $request->get('sort', 'name');
            if ($sortBy === 'name') {
                $query->orderBy('name', 'asc');
            } elseif ($sortBy === 'role') {
                $query->orderBy('role', 'asc');
            } elseif ($sortBy === 'created_at') {
                $query->orderBy('created_at', 'desc');
            }

            // Paginate results
            $users = $query->paginate(10);

            // Return JSON for AJAX requests
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'users' => $users->map(function($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'phone' => $user->phone,
                            'role' => $user->role,
                            'status' => $user->deleted_at ? 'Inactive' : 'Active',
                            'created_at' => $user->created_at->toISOString(),
                            'profile_image' => $user->profile_image ? base64_encode($user->profile_image) : null,
                            'can_view' => app(AccessControlService::class)->canViewUser($user),
                            'can_edit' => app(AccessControlService::class)->canEditUser($user),
                            'can_deactivate' => app(AccessControlService::class)->canDeactivateUser($user),
                        ];
                    }),
                    'total' => $users->total(),
                    'per_page' => $users->perPage(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                ]);
            }

            return view('users.index', compact('users'));
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new user (Staff only)
     */
    public function create()
    {
        try {
            // Access Control: Only Staff can create users
            AccessControlService::authorize('create', 'user');

            return view('users.create');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Store a newly created user in database
     * Staff can create Students, Admin can create Students and Staff
     */
    public function store(Request $request)
    {
        try {
            // Access Control: Check if user can create users
            AccessControlService::authorize('create', 'user');

            // Check if user can create the requested role
            $requestedRole = $request->input('role');
            if (!AccessControlService::canCreateRole($requestedRole)) {
                throw new \Exception('You do not have permission to create users with role: ' . $requestedRole);
            }

            // Input Validation
            $validatedData = InputValidationService::validateRegistration($request->all());

            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                $image = $request->file('profile_image');
                $request->validate([
                    'profile_image' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
                ]);
                $validatedData['profile_image'] = $this->compressImage($image->getRealPath(), 64);
            }

            // Factory Pattern: Create user based on role
            if ($validatedData['role'] === 'Student') {
                $user = UserFactory::createStudent($validatedData);
            } elseif ($validatedData['role'] === 'Staff') {
                $user = UserFactory::createStaff($validatedData);
            } elseif ($validatedData['role'] === 'Admin') {
                $user = UserFactory::createAdmin($validatedData);
            } else {
                throw new \Exception('Invalid role specified.');
            }

            return redirect()->route('users.index')
                ->with('success', 'User created successfully.');
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        try {
            // Access Control: Check if user can view this profile
            if (!AccessControlService::canViewUser($user)) {
                throw new \Exception('You do not have permission to view this profile.');
            }

            return view('users.show', compact('user'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show the form for editing the user
     */
    public function edit(User $user)
    {
        try {
            // Access Control: Check if user can edit this profile
            if (!AccessControlService::canEditUser($user)) {
                throw new \Exception('You do not have permission to edit this profile.');
            }

            return view('users.edit', compact('user'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show edit form for own profile (separate from user management)
     */
    public function editProfile()
    {
        $user = Auth::user();
        return view('users.profile-edit', compact('user'));
    }

    /**
     * Update the specified user in database
     */
    public function update(Request $request, User $user)
    {
        try {
            // Access Control: Check if user can edit this profile
            if (!AccessControlService::canEditUser($user)) {
                throw new \Exception('You do not have permission to edit this profile.');
            }

            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                $image = $request->file('profile_image');
                
                // Validate image
                $request->validate([
                    'profile_image' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
                ]);
                
                // Compress and resize image to fit BLOB (64KB)
                $imageData = $this->compressImage($image->getRealPath(), 64);
                $user->profile_image = $imageData;
            }

            // Input Validation
            $validatedData = InputValidationService::validateProfileUpdate(
                $request->all(),
                $user->id
            );

            // Factory Pattern: Update user
            UserFactory::update($user, $validatedData);
            
            // Save profile image if it was uploaded
            if ($request->hasFile('profile_image')) {
                $user->save();
            }

            return redirect()->route('users.index')
                ->with('success', 'User updated successfully.');
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update own profile (separate from user management)
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                $image = $request->file('profile_image');
                
                // Validate image
                $request->validate([
                    'profile_image' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
                ]);
                
                // Compress and resize image to fit BLOB (64KB)
                $imageData = $this->compressImage($image->getRealPath(), 64);
                
                $user->profile_image = $imageData;
            }
            
            // Validate only the fields that can be updated (not email or role)
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|min:2',
                'phone' => 'nullable|string|max:20|regex:/^[0-9\-\+\(\)\s]+$/',
                'address' => 'nullable|string|max:500',
                'password' => 'nullable|string|min:8|confirmed',
            ]);

            // Update user
            $user->name = $validatedData['name'];
            $user->phone = $validatedData['phone'] ?? null;
            $user->address = $validatedData['address'] ?? null;
            
            // Update password if provided
            if (!empty($validatedData['password'])) {
                $user->password = bcrypt($validatedData['password']);
            }
            
            $user->save();

            return redirect()->route('users.show', $user)
                ->with('success', 'Profile updated successfully.');
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Deactivate the specified user (soft delete)
     */
    public function destroy(Request $request, User $user)
    {
        try {
            // Access Control: Check if user can deactivate
            if (!AccessControlService::canDeactivateUser($user)) {
                throw new \Exception('You do not have permission to deactivate this user.');
            }

            // Factory Pattern: Deactivate user
            UserFactory::deactivate($user);

            // Return JSON for AJAX requests
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User deactivated successfully.',
                    'user' => [
                        'id' => $user->id,
                        'status' => 'Inactive'
                    ]
                ]);
            }

            return redirect()->route('users.index')
                ->with('success', 'User deactivated successfully.');
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 400);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Restore a deactivated user
     */
    public function restore(Request $request, $id)
    {
        try {
            // Access Control: Only Staff can restore users
            AccessControlService::authorize('create', 'user');

            $user = User::withTrashed()->findOrFail($id);
            $user->restore();
            UserFactory::activate($user);

            // Return JSON for AJAX requests
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User activated successfully.',
                    'user' => [
                        'id' => $user->id,
                        'status' => 'Active'
                    ]
                ]);
            }

            return redirect()->route('users.index')
                ->with('success', 'User activated successfully.');
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 400);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Compress image to fit within specified size limit (in KB)
     */
    private function compressImage($imagePath, $maxSizeKB = 64)
    {
        // Get image info
        $imageInfo = getimagesize($imagePath);
        $mimeType = $imageInfo['mime'];
        
        // Create image resource based on type
        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($imagePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($imagePath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($imagePath);
                break;
            default:
                throw new \Exception('Unsupported image type');
        }
        
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Start with reasonable dimensions (max 400x400)
        $maxDimension = 400;
        if ($width > $maxDimension || $height > $maxDimension) {
            if ($width > $height) {
                $newWidth = $maxDimension;
                $newHeight = intval($height * ($maxDimension / $width));
            } else {
                $newHeight = $maxDimension;
                $newWidth = intval($width * ($maxDimension / $height));
            }
        } else {
            $newWidth = $width;
            $newHeight = $height;
        }
        
        // Create resized image
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Try different quality levels to fit within size limit
        $quality = 85;
        $compressed = null;
        
        while ($quality > 10) {
            ob_start();
            imagejpeg($resizedImage, null, $quality);
            $compressed = ob_get_clean();
            
            $sizeKB = strlen($compressed) / 1024;
            
            if ($sizeKB <= $maxSizeKB) {
                break;
            }
            
            $quality -= 10;
            
            // If still too large, reduce dimensions
            if ($quality <= 20 && $sizeKB > $maxSizeKB) {
                $newWidth = intval($newWidth * 0.8);
                $newHeight = intval($newHeight * 0.8);
                
                imagedestroy($resizedImage);
                $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                
                $quality = 85;
            }
        }
        
        // Clean up
        imagedestroy($image);
        imagedestroy($resizedImage);
        
        return $compressed;
    }
}
