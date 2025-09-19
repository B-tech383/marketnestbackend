<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MarketNest AI - Intelligent E-commerce Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/marked/4.3.0/marked.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#fff7ed',
                            100: '#ffedd5',
                            200: '#fed7aa',
                            300: '#fdba74',
                            400: '#fb923c',
                            500: '#f97316',
                            600: '#ea580c',
                            700: '#c2410c',
                            800: '#9a3412',
                            900: '#7c2d12',
                        },
                        secondary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-out',
                        'slide-up': 'slideUp 0.4s ease-out',
                        'bounce-gentle': 'bounceGentle 2s infinite',
                        'pulse-slow': 'pulse 3s infinite',
                        'float': 'float 6s ease-in-out infinite',
                        'shimmer': 'shimmer 2.5s infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0', transform: 'translateY(10px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        slideUp: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        bounceGentle: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-5px)' }
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-15px)' }
                        },
                        shimmer: {
                            '0%': { backgroundPosition: '-200% center' },
                            '100%': { backgroundPosition: '200% center' }
                        }
                    }
                }
            }
        }
    </script>
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 50%, #e2e8f0 100%);
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .dark .glass-card {
            background: rgba(15, 23, 42, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .gradient-primary {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        }
        
        .gradient-secondary {
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
        }
        
        .shimmer-effect {
            background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.4) 50%, transparent 100%);
            background-size: 200% 100%;
            animation: shimmer 2.5s infinite;
        }
        
        .recommendation-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .recommendation-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .chat-widget {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .floating-chat {
            animation: float 6s ease-in-out infinite;
        }
        
        .typing-dots {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .typing-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #f97316;
            animation: typing 1.4s infinite ease-in-out;
        }
        
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }
        
        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); opacity: 0.3; }
            30% { transform: translateY(-10px); opacity: 1; }
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .verification-badge {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .scroll-hidden {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        
        .scroll-hidden::-webkit-scrollbar {
            display: none;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Navigation Header -->
    <nav class="glass-card border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 gradient-primary rounded-xl flex items-center justify-center">
                        <div class="relative">
                            <div class="w-6 h-6 bg-white rounded-full opacity-90"></div>
                            <i class="fas fa-store absolute top-1 left-1 text-xs text-orange-600"></i>
                        </div>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                            Market<span class="text-orange-500">Nest</span>
                        </h1>
                        <div class="text-xs text-orange-500 font-medium">AI Platform</div>
                    </div>
                </div>

                <!-- Navigation Links -->
                <div class="hidden md:flex items-center space-x-8">
                    <button onclick="showSection('dashboard')" class="nav-link text-gray-700 dark:text-gray-300 hover:text-orange-500 font-medium">Dashboard</button>
                    <button onclick="showSection('recommendations')" class="nav-link text-gray-700 dark:text-gray-300 hover:text-orange-500 font-medium">AI Recommendations</button>
                    <button onclick="showSection('vendor-dashboard')" class="nav-link text-gray-700 dark:text-gray-300 hover:text-orange-500 font-medium">Vendor Portal</button>
                    <button onclick="showSection('moderation')" class="nav-link text-gray-700 dark:text-gray-300 hover:text-orange-500 font-medium">Content Moderation</button>
                    <button onclick="showSection('verification')" class="nav-link text-gray-700 dark:text-gray-300 hover:text-orange-500 font-medium">Verification</button>
                </div>

                <!-- AI Status -->
                <div class="flex items-center space-x-3">
                    <div class="flex items-center space-x-2 bg-green-100 dark:bg-green-900/20 px-3 py-1 rounded-full">
                        <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                        <span class="text-sm font-medium text-green-700 dark:text-green-400">AI Active</span>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Dashboard Section -->
        <div id="dashboard" class="section">
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">AI Dashboard</h2>
                <p class="text-gray-600 dark:text-gray-400">Comprehensive AI-powered insights for MarketNest</p>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="glass-card rounded-xl p-6 animate-fade-in">
                    <div class="flex items-center">
                        <div class="w-12 h-12 gradient-primary rounded-lg flex items-center justify-center">
                            <i class="fas fa-robot text-white text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">AI Interactions</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">2,847</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex items-center text-sm text-green-600">
                            <i class="fas fa-arrow-up mr-1"></i>
                            <span>12% from yesterday</span>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-xl p-6 animate-fade-in" style="animation-delay: 0.1s;">
                    <div class="flex items-center">
                        <div class="w-12 h-12 gradient-secondary rounded-lg flex items-center justify-center">
                            <i class="fas fa-chart-line text-white text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Recommendations</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">15,423</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex items-center text-sm text-green-600">
                            <i class="fas fa-arrow-up mr-1"></i>
                            <span>8% from yesterday</span>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-xl p-6 animate-fade-in" style="animation-delay: 0.2s;">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-shield-alt text-white text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Content Moderated</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">342</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex items-center text-sm text-red-600">
                            <i class="fas fa-arrow-down mr-1"></i>
                            <span>3% from yesterday</span>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-xl p-6 animate-fade-in" style="animation-delay: 0.3s;">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-user-check text-white text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Verified Vendors</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">89</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex items-center text-sm text-green-600">
                            <i class="fas fa-arrow-up mr-1"></i>
                            <span>5% from yesterday</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI Performance Chart -->
            <div class="glass-card rounded-xl p-6 mb-8 animate-slide-up">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">AI Performance Analytics</h3>
                <div class="chart-container">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>
        </div>

        <!-- AI Recommendations Section -->
        <div id="recommendations" class="section hidden">
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">AI Product Recommendations</h2>
                <p class="text-gray-600 dark:text-gray-400">Personalized recommendations powered by machine learning</p>
            </div>

            <!-- Recommendation Engine Controls -->
            <div class="glass-card rounded-xl p-6 mb-8">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Recommendation Engine</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">User Segment</label>
                        <select class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 dark:bg-gray-800">
                            <option>New Customers</option>
                            <option>Returning Customers</option>
                            <option>VIP Customers</option>
                            <option>All Users</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Algorithm</label>
                        <select class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 dark:bg-gray-800">
                            <option>Collaborative Filtering</option>
                            <option>Content-Based</option>
                            <option>Hybrid Model</option>
                            <option>Deep Learning</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Priority</label>
                        <select class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 dark:bg-gray-800">
                            <option>Revenue Optimization</option>
                            <option>User Engagement</option>
                            <option>Inventory Clearance</option>
                            <option>New Product Promotion</option>
                        </select>
                    </div>
                </div>
                <button class="mt-4 gradient-primary text-white px-6 py-2 rounded-lg hover:shadow-lg transition-all duration-200">
                    Update Recommendations
                </button>
            </div>

            <!-- Recommendation Preview -->
            <div class="glass-card rounded-xl p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Live Recommendations Preview</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="recommendation-card bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-lg">
                        <div class="h-48 bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center">
                            <i class="fas fa-laptop text-white text-4xl"></i>
                        </div>
                        <div class="p-4">
                            <h4 class="font-bold text-gray-900 dark:text-white mb-2">Premium Laptop</h4>
                            <p class="text-gray-600 dark:text-gray-400 text-sm mb-2">High-performance computing</p>
                            <div class="flex items-center justify-between">
                                <span class="text-xl font-bold text-orange-500">$1,299</span>
                                <span class="text-sm text-green-600">94% match</span>
                            </div>
                        </div>
                    </div>

                    <div class="recommendation-card bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-lg">
                        <div class="h-48 bg-gradient-to-br from-green-400 to-blue-500 flex items-center justify-center">
                            <i class="fas fa-headphones text-white text-4xl"></i>
                        </div>
                        <div class="p-4">
                            <h4 class="font-bold text-gray-900 dark:text-white mb-2">Wireless Headphones</h4>
                            <p class="text-gray-600 dark:text-gray-400 text-sm mb-2">Noise-cancelling audio</p>
                            <div class="flex items-center justify-between">
                                <span class="text-xl font-bold text-orange-500">$299</span>
                                <span class="text-sm text-green-600">91% match</span>
                            </div>
                        </div>
                    </div>

                    <div class="recommendation-card bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-lg">
                        <div class="h-48 bg-gradient-to-br from-pink-400 to-red-500 flex items-center justify-center">
                            <i class="fas fa-tshirt text-white text-4xl"></i>
                        </div>
                        <div class="p-4">
                            <h4 class="font-bold text-gray-900 dark:text-white mb-2">Designer T-Shirt</h4>
                            <p class="text-gray-600 dark:text-gray-400 text-sm mb-2">Premium cotton blend</p>
                            <div class="flex items-center justify-between">
                                <span class="text-xl font-bold text-orange-500">$79</span>
                                <span class="text-sm text-green-600">88% match</span>
                            </div>
                        </div>
                    </div>

                    <div class="recommendation-card bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-lg">
                        <div class="h-48 bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center">
                            <i class="fas fa-camera text-white text-4xl"></i>
                        </div>
                        <div class="p-4">
                            <h4 class="font-bold text-gray-900 dark:text-white mb-2">Digital Camera</h4>
                            <p class="text-gray-600 dark:text-gray-400 text-sm mb-2">Professional photography</p>
                            <div class="flex items-center justify-between">
                                <span class="text-xl font-bold text-orange-500">$899</span>
                                <span class="text-sm text-green-600">85% match</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vendor Dashboard Section -->
        <div id="vendor-dashboard" class="section hidden">
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">AI Vendor Analytics</h2>
                <p class="text-gray-600 dark:text-gray-400">Intelligent insights for vendor performance optimization</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Sales Trends -->
                <div class="glass-card rounded-xl p-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Sales Predictions</h3>
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>

                <!-- Top Products -->
                <div class="glass-card rounded-xl p-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">AI-Predicted Trending Products</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-r from-green-400 to-blue-500 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-mobile-alt text-white"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">Smartphone Pro</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Expected 300% growth</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-green-600">+267%</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Next 30 days</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-r from-purple-400 to-pink-500 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-gamepad text-white"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">Gaming Console</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Holiday season boost</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-green-600">+189%</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Next 30 days</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-r from-yellow-400 to-red-500 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-watch text-white"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">Smart Watch</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Fitness trend surge</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-green-600">+156%</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Next 30 days</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Behavior Insights -->
            <div class="glass-card rounded-xl p-6 mt-8">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">AI Customer Behavior Analysis</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="w-16 h-16 gradient-primary rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-eye text-white text-2xl"></i>
                        </div>
                        <h4 class="font-bold text-gray-900 dark:text-white mb-2">Browse Patterns</h4>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">85% mobile users browse 3+ categories before purchase</p>
                    </div>
                    <div class="text-center">
                        <div class="w-16 h-16 gradient-secondary rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-clock text-white text-2xl"></i>
                        </div>
                        <h4 class="font-bold text-gray-900 dark:text-white mb-2">Peak Hours</h4>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">7-9 PM shows highest conversion rates (34% above average)</p>
                    </div>
                    <div class="text-center">
                        <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-heart text-white text-2xl"></i>
                        </div>
                        <h4 class="font-bold text-gray-900 dark:text-white mb-2">Preferences</h4>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">Fast shipping (2-day) increases purchase likelihood by 67%</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Moderation Section -->
        <div id="moderation" class="section hidden">
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">AI Content Moderation</h2>
                <p class="text-gray-600 dark:text-gray-400">Automated detection and review of marketplace content</p>
            </div>

            <!-- Moderation Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="glass-card rounded-xl p-6 text-center">
                    <div class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-exclamation-triangle text-white"></i>
                    </div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">23</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Flagged Items</p>
                </div>
                <div class="glass-card rounded-xl p-6 text-center">
                    <div class="w-12 h-12 bg-yellow-500 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-search text-white"></i>
                    </div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">156</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Under Review</p>
                </div>
                <div class="glass-card rounded-xl p-6 text-center">
                    <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-check text-white"></i>
                    </div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">1,247</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Approved</p>
                </div>
                <div class="glass-card rounded-xl p-6 text-center">
                    <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-robot text-white"></i>
                    </div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">99.2%</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">AI Accuracy</p>
                </div>
            </div>

            <!-- Recent Moderation Actions -->
            <div class="glass-card rounded-xl p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Recent AI Moderation Actions</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 border border-red-200 dark:border-red-800 rounded-lg bg-red-50 dark:bg-red-900/20">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-red-500 rounded-lg flex items-center justify-center">
                                <i class="fas fa-times text-white"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">Counterfeit Designer Bag Detected</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Vendor: FashionWorld2023 • Product ID: #12847</p>
                                <p class="text-xs text-red-600 mt-1">AI Confidence: 94.7% • Action: Removed</p>
                            </div>
                        </div>
                        <span class="text-xs text-gray-500">2 min ago</span>
                    </div>

                    <div class="flex items-center justify-between p-4 border border-yellow-200 dark:border-yellow-800 rounded-lg bg-yellow-50 dark:bg-yellow-900/20">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-yellow-500 rounded-lg flex items-center justify-center">
                                <i class="fas fa-exclamation-triangle text-white"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">Inappropriate Product Description</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Vendor: TechGuru99 • Product ID: #15934</p>
                                <p class="text-xs text-yellow-600 mt-1">AI Confidence: 87.3% • Action: Flagged for review</p>
                            </div>
                        </div>
                        <span class="text-xs text-gray-500">8 min ago</span>
                    </div>

                    <div class="flex items-center justify-between p-4 border border-green-200 dark:border-green-800 rounded-lg bg-green-50 dark:bg-green-900/20">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                                <i class="fas fa-check text-white"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">Product Listing Approved</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Vendor: ElectronicsPlus • Product ID: #16782</p>
                                <p class="text-xs text-green-600 mt-1">AI Confidence: 99.1% • Action: Auto-approved</p>
                            </div>
                        </div>
                        <span class="text-xs text-gray-500">15 min ago</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vendor Verification Section -->
        <div id="verification" class="section hidden">
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">AI Vendor Verification</h2>
                <p class="text-gray-600 dark:text-gray-400">Automated vendor verification and badge assignment system</p>
            </div>

            <!-- Verification Pipeline -->
            <div class="glass-card rounded-xl p-6 mb-8">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Verification Pipeline</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-blue-500 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-file-alt text-white text-2xl"></i>
                        </div>
                        <h4 class="font-bold text-gray-900 dark:text-white mb-2">Document Scan</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">AI analyzes business documents for authenticity</p>
                    </div>
                    <div class="text-center">
                        <div class="w-16 h-16 bg-purple-500 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-search text-white text-2xl"></i>
                        </div>
                        <h4 class="font-bold text-gray-900 dark:text-white mb-2">Background Check</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Cross-reference with business registries and databases</p>
                    </div>
                    <div class="text-center">
                        <div class="w-16 h-16 bg-yellow-500 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-star text-white text-2xl"></i>
                        </div>
                        <h4 class="font-bold text-gray-900 dark:text-white mb-2">Reputation Analysis</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Review history and customer feedback patterns</p>
                    </div>
                    <div class="text-center">
                        <div class="w-16 h-16 verification-badge rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-certificate text-white text-2xl"></i>
                        </div>
                        <h4 class="font-bold text-gray-900 dark:text-white mb-2">Badge Assignment</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Automatic verification badge upon approval</p>
                    </div>
                </div>
            </div>

            <!-- Recent Verifications -->
            <div class="glass-card rounded-xl p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Recent Verification Results</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 border border-green-200 dark:border-green-800 rounded-lg bg-green-50 dark:bg-green-900/20">
                        <div class="flex items-center space-x-4">
                            <div class="verification-badge w-12 h-12 rounded-lg flex items-center justify-center">
                                <i class="fas fa-certificate text-white"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">TechSolutions Inc.</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Business License: Verified • Tax ID: Valid • Reviews: 4.8/5</p>
                                <p class="text-xs text-green-600 mt-1">AI Verification Score: 96.4% • Status: Verified</p>
                            </div>
                        </div>
                        <div class="verification-badge px-3 py-1 rounded-full">
                            <span class="text-white text-sm font-medium">Verified</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-4 border border-yellow-200 dark:border-yellow-800 rounded-lg bg-yellow-50 dark:bg-yellow-900/20">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-yellow-500 rounded-lg flex items-center justify-center">
                                <i class="fas fa-clock text-white"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">Global Fashion Hub</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Business License: Pending • Tax ID: Under Review • Reviews: 4.2/5</p>
                                <p class="text-xs text-yellow-600 mt-1">AI Verification Score: 78.2% • Status: In Progress</p>
                            </div>
                        </div>
                        <div class="bg-yellow-500 px-3 py-1 rounded-full">
                            <span class="text-white text-sm font-medium">Pending</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-4 border border-red-200 dark:border-red-800 rounded-lg bg-red-50 dark:bg-red-900/20">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-red-500 rounded-lg flex items-center justify-center">
                                <i class="fas fa-times text-white"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">QuickDeals LLC</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Business License: Invalid • Tax ID: Not Found • Reviews: 2.1/5</p>
                                <p class="text-xs text-red-600 mt-1">AI Verification Score: 23.7% • Status: Rejected</p>
                            </div>
                        </div>
                        <div class="bg-red-500 px-3 py-1 rounded-full">
                            <span class="text-white text-sm font-medium">Rejected</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Chat Widget -->
    <div class="chat-widget">
        <div id="chatToggle" class="floating-chat w-16 h-16 gradient-primary rounded-full flex items-center justify-center cursor-pointer shadow-lg hover:shadow-xl transition-all duration-300" onclick="toggleChat()">
            <i class="fas fa-comments text-white text-2xl"></i>
            <div class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 rounded-full flex items-center justify-center">
                <span class="text-white text-xs font-bold">3</span>
            </div>
        </div>

        <!-- Chat Window -->
        <div id="chatWindow" class="hidden absolute bottom-20 right-0 w-96 h-96 glass-card rounded-xl shadow-2xl">
            <div class="gradient-primary p-4 rounded-t-xl text-white">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-robot text-sm"></i>
                        </div>
                        <div>
                            <h4 class="font-bold">MarketNest AI</h4>
                            <p class="text-xs opacity-80">Always here to help</p>
                        </div>
                    </div>
                    <button onclick="toggleChat()" class="text-white hover:bg-white/20 p-1 rounded">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div class="p-4 h-64 overflow-y-auto scroll-hidden" id="chatMessages">
                <div class="mb-4">
                    <div class="flex items-start space-x-2">
                        <div class="w-8 h-8 gradient-primary rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-robot text-white text-xs"></i>
                        </div>
                        <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-3 max-w-xs">
                            <p class="text-sm text-gray-800 dark:text-gray-200">Hello! I'm your MarketNest AI assistant. How can I help you today?</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex space-x-2">
                    <input
                        type="text"
                        id="chatInput"
                        placeholder="Ask me anything..."
                        class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-base focus:ring-2 focus:ring-orange-500 dark:bg-gray-800"
                        onkeypress="handleChatKeypress(event)"
                    >
                    <button onclick="sendChatMessage()" class="gradient-primary text-white px-4 py-2 rounded-lg hover:shadow-lg transition-all duration-200">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Dark mode handling
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', event => {
            if (event.matches) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        });

        // Section Navigation
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.section').forEach(section => {
                section.classList.add('hidden');
            });
            
            // Show selected section
            document.getElementById(sectionId).classList.remove('hidden');
            
            // Update navigation
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('text-orange-500');
                link.classList.add('text-gray-700', 'dark:text-gray-300');
            });
            
            event.target.classList.remove('text-gray-700', 'dark:text-gray-300');
            event.target.classList.add('text-orange-500');
        }

        // Chat functionality
        function toggleChat() {
            const chatWindow = document.getElementById('chatWindow');
            chatWindow.classList.toggle('hidden');
        }

        function handleChatKeypress(event) {
            if (event.key === 'Enter') {
                sendChatMessage();
            }
        }

        async function sendChatMessage() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            if (!message) return;

            // Add user message to chat
            addChatMessage(message, true);
            input.value = '';

            // Show typing indicator
            showTypingIndicator();

            // Send to AI
            try {
                await window.Poe.sendUserMessage(`@Claude-Sonnet-4 You are the MarketNest AI assistant. Help with e-commerce questions, product recommendations, order tracking, and vendor support. User question: "${message}"`, {
                    handler: "marketnest-chat-handler",
                    stream: true,
                    openChat: false
                });
            } catch (error) {
                hideTypingIndicator();
                addChatMessage("Sorry, I'm having trouble connecting. Please try again.", false);
            }
        }

        function addChatMessage(message, isUser) {
            const chatMessages = document.getElementById('chatMessages');
            const messageHTML = `
                <div class="mb-4">
                    <div class="flex items-start space-x-2 ${isUser ? 'flex-row-reverse space-x-reverse' : ''}">
                        <div class="w-8 h-8 ${isUser ? 'bg-gradient-to-r from-blue-500 to-purple-500' : 'gradient-primary'} rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas ${isUser ? 'fa-user' : 'fa-robot'} text-white text-xs"></i>
                        </div>
                        <div class="${isUser ? 'bg-gradient-to-r from-blue-500 to-purple-500 text-white' : 'bg-gray-100 dark:bg-gray-700'} rounded-lg p-3 max-w-xs">
                            <p class="text-sm ${isUser ? 'text-white' : 'text-gray-800 dark:text-gray-200'}">${message}</p>
                        </div>
                    </div>
                </div>
            `;
            chatMessages.insertAdjacentHTML('beforeend', messageHTML);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function showTypingIndicator() {
            const chatMessages = document.getElementById('chatMessages');
            const typingHTML = `
                <div class="mb-4" id="typingIndicator">
                    <div class="flex items-start space-x-2">
                        <div class="w-8 h-8 gradient-primary rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-robot text-white text-xs"></i>
                        </div>
                        <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-3">
                            <div class="typing-dots">
                                <div class="typing-dot"></div>
                                <div class="typing-dot"></div>
                                <div class="typing-dot"></div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            chatMessages.insertAdjacentHTML('beforeend', typingHTML);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function hideTypingIndicator() {
            const indicator = document.getElementById('typingIndicator');
            if (indicator) indicator.remove();
        }

        // Register AI response handler
        window.Poe.registerHandler("marketnest-chat-handler", (result, context) => {
            if (result.responses.length > 0) {
                const response = result.responses[0];
                if (response.status === "complete") {
                    hideTypingIndicator();
                    addChatMessage(response.content, false);
                }
            }
        });

        // Initialize charts
        function initCharts() {
            // Performance Chart
            const performanceCtx = document.getElementById('performanceChart').getContext('2d');
            new Chart(performanceCtx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'AI Interactions',
                        data: [1200, 1900, 3000, 2500, 2800, 3200, 2847],
                        borderColor: '#f97316',
                        backgroundColor: 'rgba(249, 115, 22, 0.1)',
                        fill: true,
                        tension: 0.4
                    }, {
                        label: 'Recommendations',
                        data: [800, 1600, 2100, 1800, 2200, 2800, 2400],
                        borderColor: '#0ea5e9',
                        backgroundColor: 'rgba(14, 165, 233, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Sales Chart
            const salesCtx = document.getElementById('salesChart').getContext('2d');
            new Chart(salesCtx, {
                type: 'bar',
                data: {
                    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                    datasets: [{
                        label: 'Predicted Sales',
                        data: [45000, 52000, 48000, 58000],
                        backgroundColor: 'rgba(249, 115, 22, 0.8)',
                        borderColor: '#f97316',
                        borderWidth: 2
                    }, {
                        label: 'Actual Sales',
                        data: [42000, 49000, 51000, null],
                        backgroundColor: 'rgba(14, 165, 233, 0.8)',
                        borderColor: '#0ea5e9',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initCharts, 500);
        });
    </script>
</body>
</html>