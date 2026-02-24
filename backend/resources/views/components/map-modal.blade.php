<div class="modal fade" id="mapModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header border-bottom shadow-sm">
                <h5 class="modal-title fw-bold">Logistics Command Center</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" style="height: calc(100vh - 56px); position: relative;">
                <div id="modalLogisticsMap" style="height: 100vh; width: 100%; min-height: 400px;"></div>

                <div id="modal-map-controls-container" style="position: absolute; top: 10px; left: 10px; z-index: 1100;">
                    <div id="eta-display-container-modal" style="display: none; margin-bottom: 10px; background: rgba(255,255,255,0.95); padding: 6px 10px; border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.12);">
                    </div>

                    <div id="route-loading-spinner-modal" style="display: none; align-items: center; gap:8px; margin-bottom: 6px; background: rgba(255,255,255,0.95); padding: 6px 10px; border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.12);">
                        <div class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></div>
                        <div class="spinner-text" style="font-size:13px; color:#1f2937;">Loading route&hellip;</div>
                    </div>

                    <div class="route-controls-modal" style="display: none; margin-top: 6px; background: rgba(255,255,255,0.95); padding: 6px; border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.12);">
                        <button class="route-btn btn-clear-route" onclick="clearRouteFromMaps()">
                            <i class="bi bi-x-circle"></i> Clear Route
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
