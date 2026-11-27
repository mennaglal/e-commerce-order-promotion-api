<aside class="sidebar-left border-right bg-white shadow"
       id="leftSidebar"
       data-simplebar>

    <a href="#"
       class="btn collapseSidebar toggle-btn d-lg-none text-muted ml-2 mt-3"
       data-toggle="toggle">
        <i class="fe fe-x"><span class="sr-only"></span></i>
    </a>

    <nav class="vertnav navbar navbar-light">

        {{-- Logo --}}
        <div class="w-100 mb-4 d-flex">
            <a class="navbar-brand mx-auto mt-2 flex-fill text-center"
               href="{{ route('admin.dashboard') }}">
                <img id="logo"
                     class="navbar-brand-img brand-sm"
                     src="{{ asset('assets/images/logo.png') }}"
                     style="width: 3rem">
            </a>
        </div>

        {{-- Dashboard --}}
        <ul class="navbar-nav flex-fill w-100 mb-2">
            <li class="nav-item w-100">
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                   href="{{ route('admin.dashboard') }}">
                    <i class="fe fe-home text-primary"></i>
                    <span class="ml-3 item-text">Dashboard</span>
                </a>
            </li>
        </ul>

        @php
            $sections = [
                [
                    'id' => 'products',
                    'icon' => 'fe fe-box',
                    'label' => 'Products',
                    'items' => [
                        [
                            'route' => 'admin.products.index',
                            'text' => 'All Products',
                        ],
                        [
                            'route' => 'admin.products.create',
                            'text' => 'Add Product',
                        ],
                    ],
                ],
                [
                    'id' => 'promotion-codes',
                    'icon' => 'fe fe-tag',
                    'label' => 'Promotion Codes',
                    'items' => [
                        [
                            'route' => 'admin.promotion-codes.index',
                            'text' => 'All Promotion Codes',
                        ],
                        [
                            'route' => 'admin.promotion-codes.create',
                            'text' => 'Add Promotion Code',
                        ],
                    ],
                ],
            ];
        @endphp

        {{-- Sidebar Sections --}}
        @foreach($sections as $section)

            @php
                $sectionActive = collect($section['items'])->contains(
                    fn ($item) => request()->routeIs($item['route'])
                );
            @endphp

            <ul class="navbar-nav flex-fill w-100 mb-2">
                <li class="nav-item dropdown {{ $sectionActive ? 'active' : '' }}"
                    id="{{ $section['id'] }}-item">

                    <a href="#{{ $section['id'] }}-collapse"
                       data-toggle="collapse"
                       aria-expanded="{{ $sectionActive ? 'true' : 'false' }}"
                       class="dropdown-toggle nav-link {{ $sectionActive ? 'active' : '' }}">

                        <i class="{{ $section['icon'] }} text-primary"></i>

                        <span class="ml-3 item-text">
                            {{ $section['label'] }}
                        </span>
                    </a>

                    <ul class="collapse list-unstyled pl-4 w-100 {{ $sectionActive ? 'show' : '' }}"
                        id="{{ $section['id'] }}-collapse">

                        @foreach($section['items'] as $item)
                            <li>
                                <a class="nav-link pl-3 {{ request()->routeIs($item['route']) ? 'active' : '' }}"
                                   href="{{ route($item['route']) }}">

                                    <span class="ml-1 item-text">
                                        {{ $item['text'] }}
                                    </span>
                                </a>
                            </li>
                        @endforeach

                    </ul>
                </li>
            </ul>

        @endforeach

        {{-- Collapse Memory Script --}}
        <script>
            document.addEventListener('DOMContentLoaded', function () {

                const sections = [
                    'products',
                    'promotion-codes'
                ];

                sections.forEach(id => {

                    const collapseId = `#${id}-collapse`;
                    const storageKey = `${id}Open`;

                    if (localStorage.getItem(storageKey) === 'true') {
                        $(collapseId).collapse('show');
                    }

                    $(collapseId).on('show.bs.collapse', function () {

                        localStorage.setItem(storageKey, 'true');

                        sections.filter(section => section !== id).forEach(section => {
                            $(`#${section}-collapse`).collapse('hide');
                            localStorage.setItem(`${section}Open`, 'false');
                        });

                    });

                    $(collapseId).on('hide.bs.collapse', function () {
                        localStorage.setItem(storageKey, 'false');
                    });

                });

            });
        </script>

    </nav>
</aside>
