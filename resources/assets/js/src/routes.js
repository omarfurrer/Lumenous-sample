import DashView from './components/Dash.vue'
        import LoginView from './components/Login.vue'
        import RegisterView from './components/Register.vue'
        import NotFoundView from './components/404.vue'
        import VerifyPublicKeyView from './components/VerifyPublicKey.vue'

// Import Views - Dash
        import DashboardView from './components/views/Dashboard.vue'
        import PayoutsView from './components/views/Payouts.vue'
        import TablesView from './components/views/Tables.vue'
        import TasksView from './components/views/Tasks.vue'
        import SettingView from './components/views/Setting.vue'
        import AccessView from './components/views/Access.vue'
        import ServerView from './components/views/Server.vue'
        import ReposView from './components/views/Repos.vue'
        import SignTransactionBatchView from './components/views/admin/SignTransactionBatch.vue'
        import IndexTransactionBatchesView from './components/views/admin/IndexTransactionBatches.vue'

// Routes
        const routes = [
            {
                path: '/login',
                component: LoginView
            },
            {
                path: '/register',
                component: RegisterView
            },
            {
                path: '/verify/key/public',
                component: VerifyPublicKeyView
            },
            {
                path: '/dashboard',
                component: DashView,
                meta: {requiresAuth: true},
                children: [
                    {
                        path: 'main',
                        alias: '',
                        component: DashboardView,
                        name: 'Dashboard',
                        meta: {description: 'Overview of environment'}
                    }, {
                        path: 'payouts',
                        component: PayoutsView,
                        name: 'Payouts',
                        meta: {description: 'Account payouts'}
                    }, {
                        path: 'tables',
                        component: TablesView,
                        name: 'Tables',
                        meta: {description: 'Simple and advance table in CoPilot'}
                    }, {
                        path: 'tasks',
                        component: TasksView,
                        name: 'Tasks',
                        meta: {description: 'Tasks page in the form of a timeline'}
                    }, {
                        path: 'setting',
                        component: SettingView,
                        name: 'Settings',
                        meta: {description: 'User settings page'}
                    }, {
                        path: 'access',
                        component: AccessView,
                        name: 'Access',
                        meta: {description: 'Example of using maps'}
                    }, {
                        path: 'server',
                        component: ServerView,
                        name: 'Servers',
                        meta: {description: 'List of our servers', requiresAuth: true}
                    }, {
                        path: 'repos',
                        component: ReposView,
                        name: 'Repository',
                        meta: {description: 'List of popular javascript repos'}
                    },
                    {
                        path: 'admin/transactions/batches/:id/sign',
                        component: SignTransactionBatchView,
                        name: 'Sign Transaction Batch',
                        meta: {description: 'Sign transaction batch'}
                    },
                    {
                        path: 'admin/transactions/batches',
                        component: IndexTransactionBatchesView,
                        name: 'Index Transaction Batches',
                        meta: {description: 'View all transaction batches'}
                    }
//                    {
//                        path: 'admin',
//                        children: [
//                            {
//                                path: 'transactions/batches/:id/sign',
//                                component: SignTransactionBatchView,
//                                name: 'Sign Transaction Batch',
//                                meta: {description: 'Sign transaction batch'}
//                            }
//                        ]
//                    }

                ]
            }, {
                // not found handler
                path: '*',
                component: NotFoundView
            }
        ]

export default routes
