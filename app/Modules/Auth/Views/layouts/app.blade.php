<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'MyMoney' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-body">
    <nav class="app-nav">
        <div class="container app-nav-inner">
            <a class="brand-mark" href="{{ auth()->check() ? route('dashboard') : '/' }}">
                <span class="brand-mark-icon">M</span>
                <span>MyMoney</span>
            </a>
            @auth
                <div class="app-nav-links">
                    <a href="{{ route('dashboard') }}" class="app-nav-link">Overview</a>
                    <a href="{{ route('send.money') }}" class="app-nav-link">Send money</a>
                    <a href="{{ route('request.money') }}" class="app-nav-link">Request</a>
                    <a href="{{ route('transaction.history') }}" class="app-nav-link">Activity</a>
                    <a href="{{ route('profile') }}" class="app-nav-link">Profile</a>
                    <a href="{{ route('settings') }}" class="app-nav-link">Settings</a>
                </div>

                <div class="dropdown app-nav-actions">
                    <button class="notification-button position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                        <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        @php $unread = auth()->user()->unreadNotifications()->count(); @endphp
                        @if($unread)
                            <span class="notification-count">{{ $unread }}</span>
                        @endif
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="min-width: 300px;">
                        @forelse(auth()->user()->notifications()->latest()->limit(5)->get() as $notification)
                            @php($data = $notification->data)
                            <li class="border-bottom">
                                <button type="button" class="dropdown-item notification-trigger px-3 py-2 text-wrap" data-notification='@json($data)' data-notification-id="{{ $notification->id }}" data-read-url="{{ route('notifications.read', $notification->id) }}">
                                    <div class="fw-semibold">{{ data_get($data, 'title') }}</div>
                                    <small class="text-muted">{{ data_get($data, 'message') }}</small>
                                </button>
                            </li>
                        @empty
                            <li class="px-3 py-2 text-muted">No notifications</li>
                        @endforelse
                    </ul>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="logout-form">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-quiet">Log out</button>
                </form>
            @else
                <div class="app-nav-actions">
                    <a href="{{ route('login') }}" class="app-nav-link">Log in</a>
                    <a href="{{ route('register') }}" class="btn btn-accent btn-sm">Create account</a>
                </div>
            @endauth
        </div>
    </nav>
    <main class="container page-shell">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        @yield('content')
    </main>
    <div class="modal fade" id="notificationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notificationModalTitle">Notification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <img id="notificationModalPhoto" src="" alt="Account holder" class="rounded-circle border" width="64" height="64">
                        <div>
                            <div id="notificationModalName" class="fw-bold"></div>
                            <div id="notificationModalAccount" class="small text-muted"></div>
                            <div id="notificationModalAmount" class="text-muted"></div>
                        </div>
                    </div>
                    <p id="notificationModalMessage" class="mb-0"></p>
                    <form id="notificationActionForm" class="mt-3 d-none" method="POST">
                        @csrf
                        <div id="notificationOtpGroup" class="d-none">
                            <label for="notificationOtp" class="form-label">Receiver OTP</label>
                            <input type="text" id="notificationOtp" name="otp" class="form-control" inputmode="numeric" maxlength="6" pattern="[0-9]{6}">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="notificationActionForm" id="notificationActionButton" class="btn btn-primary d-none">Send</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.recipient-preview-form').forEach((form) => {
            const input = form.querySelector('input[name="to_user_id"]');
            const preview = form.querySelector('.recipient-preview');
            let timer;
            input.addEventListener('input', () => {
                clearTimeout(timer);
                preview.classList.add('d-none');
                const value = input.value.trim();
                if (!value) return;
                timer = setTimeout(async () => {
                    const response = await fetch('{{ route('transfer.recipient-preview') }}?to_user_id=' + encodeURIComponent(value), { headers: { 'Accept': 'application/json' } });
                    const data = await response.json();
                    if (!data.found) return;
                    preview.innerHTML = '<img src="' + data.photo_url + '" alt="' + data.name + '" class="rounded-circle me-2" width="40" height="40"> <strong>' + data.name + '</strong>' + (data.account_number ? ' <span class="text-muted">(' + data.account_number + ')</span>' : '');
                    preview.classList.remove('d-none');
                }, 300);
            });
        });

        document.querySelectorAll('.notification-trigger').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                const data = JSON.parse(trigger.dataset.notification);
                fetch(trigger.dataset.readUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                }).then((response) => response.json()).then((result) => {
                    const count = document.querySelector('.notification-count');
                    if (count) {
                        count.textContent = result.unread;
                        count.hidden = result.unread === 0;
                    }
                });
                const form = document.getElementById('notificationActionForm');
                const actionButton = document.getElementById('notificationActionButton');
                const otp = document.getElementById('notificationOtp');
                document.getElementById('notificationModalTitle').textContent = data.title || 'Notification';
                document.getElementById('notificationModalMessage').textContent = data.message || '';
                document.getElementById('notificationModalName').textContent = data.sender_name || '';
                document.getElementById('notificationModalAccount').textContent = data.sender_account_number ? data.sender_account_number : '';
                document.getElementById('notificationModalAmount').textContent = data.amount ? '৳' + Number(data.amount).toFixed(2) : '';
                document.getElementById('notificationModalPhoto').src = data.sender_photo_url || 'https://ui-avatars.com/api/?name=User';
                form.reset();
                form.classList.toggle('d-none', !data.action_url);
                actionButton.classList.toggle('d-none', !data.action_url);
                actionButton.textContent = data.action_type === 'receiver_transfer'
                    ? 'Receive money'
                    : (data.receiver_otp_enabled ? 'Generate receiver OTP' : 'Send');
                form.action = data.action_url || '#';
                document.getElementById('notificationOtpGroup').classList.toggle('d-none', data.action_type !== 'receiver_transfer');
                otp.required = data.action_type === 'receiver_transfer';
                bootstrap.Modal.getOrCreateInstance(document.getElementById('notificationModal')).show();
            });
        });
    </script>
</body>
</html>
