{{-- $user (nullable), $roles --}}
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Nombre completo</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $user?->name) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Correo</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $user?->email) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Contraseña {{ $user ? '(vacío = sin cambios)' : '' }}</label>
        <input type="password" name="password" class="form-control" minlength="8" @required(!$user)>
    </div>
    <div class="col-md-6">
        <label class="form-label">Rol</label>
        <select name="role" class="form-select" required>
            @foreach($roles as $role)
                <option value="{{ $role->name }}" @selected(old('role', $user?->roles->first()?->name) === $role->name)>{{ $role->description ?? $role->name }}</option>
            @endforeach
        </select>
    </div>
</div>
