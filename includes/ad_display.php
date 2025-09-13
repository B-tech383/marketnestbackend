<?php
require_once __DIR__ . '/advertisement.php';

class AdDisplay {
    private $adManager;
    
    public function __construct() {
        $this->adManager = new AdvertisementManager();
    }
    
    public function displayAds($ad_type = null, $position = null, $limit = 1) {
        $ads = $this->adManager->getActiveAds($ad_type, $position);
        
        if (empty($ads)) {
            return '';
        }
        
        // Limit the number of ads
        $ads = array_slice($ads, 0, $limit);
        
        $html = '';
        foreach ($ads as $ad) {
            $html .= $this->renderAd($ad);
        }
        
        return $html;
    }
    
    private function renderAd($ad) {
        // Increment view count
        $this->adManager->incrementViewCount($ad['id']);
        
        $html = '<div class="ad-container" data-ad-id="' . $ad['id'] . '">';
        
        if ($ad['ad_type'] === 'banner') {
            $html .= $this->renderBannerAd($ad);
        } elseif ($ad['ad_type'] === 'sidebar') {
            $html .= $this->renderSidebarAd($ad);
        } elseif ($ad['ad_type'] === 'popup') {
            $html .= $this->renderPopupAd($ad);
        } elseif ($ad['ad_type'] === 'inline') {
            $html .= $this->renderInlineAd($ad);
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    private function renderBannerAd($ad) {
        $clickable = !empty($ad['link_url']);
        $tag = $clickable ? 'a' : 'div';
        $href = $clickable ? 'href="' . htmlspecialchars($ad['link_url']) . '" target="_blank"' : '';
        
        $html = '<' . $tag . ' ' . $href . ' class="banner-ad w-full bg-gradient-to-r from-blue-500 to-purple-600 text-white p-4 rounded-lg mb-4 cursor-pointer hover:shadow-lg transition-shadow" onclick="trackAdClick(' . $ad['id'] . ')">';
        
        if ($ad['image_url']) {
            $html .= '<img src="' . htmlspecialchars($ad['image_url']) . '" alt="' . htmlspecialchars($ad['title']) . '" class="w-full h-32 object-cover rounded mb-2">';
        }
        
        if ($ad['video_url']) {
            $html .= '<video class="w-full h-32 object-cover rounded mb-2" autoplay muted loop>';
            $html .= '<source src="' . htmlspecialchars($ad['video_url']) . '" type="video/mp4">';
            $html .= '</video>';
        }
        
        $html .= '<h3 class="text-lg font-bold mb-2">' . htmlspecialchars($ad['title']) . '</h3>';
        
        if ($ad['content']) {
            $html .= '<p class="text-sm opacity-90">' . htmlspecialchars($ad['content']) . '</p>';
        }
        
        $html .= '</' . $tag . '>';
        
        return $html;
    }
    
    private function renderSidebarAd($ad) {
        $clickable = !empty($ad['link_url']);
        $tag = $clickable ? 'a' : 'div';
        $href = $clickable ? 'href="' . htmlspecialchars($ad['link_url']) . '" target="_blank"' : '';
        
        $html = '<' . $tag . ' ' . $href . ' class="sidebar-ad bg-white border border-gray-200 rounded-lg p-4 mb-4 shadow-sm hover:shadow-md transition-shadow cursor-pointer" onclick="trackAdClick(' . $ad['id'] . ')">';
        
        if ($ad['image_url']) {
            $html .= '<img src="' . htmlspecialchars($ad['image_url']) . '" alt="' . htmlspecialchars($ad['title']) . '" class="w-full h-24 object-cover rounded mb-2">';
        }
        
        $html .= '<h4 class="font-semibold text-gray-900 mb-1">' . htmlspecialchars($ad['title']) . '</h4>';
        
        if ($ad['content']) {
            $html .= '<p class="text-sm text-gray-600">' . htmlspecialchars($ad['content']) . '</p>';
        }
        
        $html .= '</' . $tag . '>';
        
        return $html;
    }
    
    private function renderPopupAd($ad) {
        $clickable = !empty($ad['link_url']);
        $tag = $clickable ? 'a' : 'div';
        $href = $clickable ? 'href="' . htmlspecialchars($ad['link_url']) . '" target="_blank"' : '';
        
        $html = '<div class="popup-ad fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" id="popup-ad-' . $ad['id'] . '">';
        $html .= '<div class="bg-white rounded-lg p-6 max-w-md mx-4 relative">';
        $html .= '<button onclick="closePopupAd(' . $ad['id'] . ')" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">×</button>';
        
        $html .= '<' . $tag . ' ' . $href . ' onclick="trackAdClick(' . $ad['id'] . ')" class="block">';
        
        if ($ad['image_url']) {
            $html .= '<img src="' . htmlspecialchars($ad['image_url']) . '" alt="' . htmlspecialchars($ad['title']) . '" class="w-full h-48 object-cover rounded mb-3">';
        }
        
        if ($ad['video_url']) {
            $html .= '<video class="w-full h-48 object-cover rounded mb-3" autoplay muted loop>';
            $html .= '<source src="' . htmlspecialchars($ad['video_url']) . '" type="video/mp4">';
            $html .= '</video>';
        }
        
        $html .= '<h3 class="text-xl font-bold text-gray-900 mb-2">' . htmlspecialchars($ad['title']) . '</h3>';
        
        if ($ad['content']) {
            $html .= '<p class="text-gray-600">' . htmlspecialchars($ad['content']) . '</p>';
        }
        
        $html .= '</' . $tag . '>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    private function renderInlineAd($ad) {
        $clickable = !empty($ad['link_url']);
        $tag = $clickable ? 'a' : 'div';
        $href = $clickable ? 'href="' . htmlspecialchars($ad['link_url']) . '" target="_blank"' : '';
        
        $html = '<' . $tag . ' ' . $href . ' class="inline-ad bg-gray-50 border-l-4 border-blue-500 p-4 my-4 rounded-r-lg hover:bg-gray-100 transition-colors cursor-pointer" onclick="trackAdClick(' . $ad['id'] . ')">';
        
        if ($ad['image_url']) {
            $html .= '<img src="' . htmlspecialchars($ad['image_url']) . '" alt="' . htmlspecialchars($ad['title']) . '" class="w-full h-32 object-cover rounded mb-2">';
        }
        
        $html .= '<h4 class="font-semibold text-gray-900 mb-1">' . htmlspecialchars($ad['title']) . '</h4>';
        
        if ($ad['content']) {
            $html .= '<p class="text-sm text-gray-600">' . htmlspecialchars($ad['content']) . '</p>';
        }
        
        $html .= '</' . $tag . '>';
        
        return $html;
    }
}

// JavaScript for tracking ad clicks
function getAdTrackingScript() {
    return '
    <script>
    function trackAdClick(adId) {
        fetch("api/track-ad-click.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ad_id: adId})
        }).catch(error => console.log("Ad tracking error:", error));
    }
    
    function closePopupAd(adId) {
        document.getElementById("popup-ad-" + adId).style.display = "none";
    }
    
    // Auto-show popup ads after 3 seconds
    document.addEventListener("DOMContentLoaded", function() {
        setTimeout(function() {
            const popupAds = document.querySelectorAll(".popup-ad");
            popupAds.forEach(function(ad) {
                ad.style.display = "flex";
            });
        }, 3000);
    });
    </script>';
}
?>

