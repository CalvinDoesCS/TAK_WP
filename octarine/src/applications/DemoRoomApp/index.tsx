import React, { useState, useEffect } from "react";
import { Window, DraggableHandle, ControlButtons } from "@/components/Window";
import { useWasmLoader } from "@/hooks/wasm";

/**
 * DemoRoomApp - Simple WASM Demo Room
 *
 * Standalone app that loads the demo-room WASM module
 * and displays a single room's controls.
 * Uses the reusable WASM template for loading.
 */

interface RoomState {
  lighting: number;
  temperature: number;
  devices: Array<{
    id: number;
    name: string;
    status: boolean;
    power: number;
  }>;
}

function DemoRoomApp() {
  const wasmInitRef = React.useRef(false); // Track if we've initialized WASM

  const {
    exports: wasm,
    loading,
    error,
  } = useWasmLoader({
    moduleName: "demo_room",
  });

  const [roomState, setRoomState] = React.useState<RoomState>(() => {
    // Load from localStorage if available
    try {
      const saved = localStorage.getItem("demo-room-state");
      if (saved) {
        const parsed = JSON.parse(saved);
        console.log("💾 Loaded state from localStorage:", parsed);
        return parsed;
      }
    } catch (err) {
      console.error("Failed to load from localStorage:", err);
    }
    return {
      lighting: 50,
      temperature: 21,
      devices: [],
    };
  });

  // Auto-save state to localStorage whenever it changes
  useEffect(() => {
    localStorage.setItem("demo-room-state", JSON.stringify(roomState));
    console.log("💾 Saved state to localStorage");
  }, [roomState]);

  // Initialize room state from WASM when loaded (only once)
  useEffect(() => {
    if (wasm?.get_state_json && !wasmInitRef.current) {
      wasmInitRef.current = true;
      try {
        // First check if we have saved state from localStorage
        const savedState = localStorage.getItem("demo-room-state");
        let stateToApply = roomState;

        if (savedState) {
          console.log("💾 Found saved state in localStorage, restoring...");
          const parsed = JSON.parse(savedState);
          stateToApply = parsed;
        } else {
          // No saved state, read from WASM defaults
          const stateJson = wasm.get_state_json(1);
          console.log("📥 Raw state from WASM:", stateJson);
          const state = JSON.parse(stateJson);
          console.log("📊 Parsed state:", state);
          stateToApply = {
            lighting: state.lighting || 50,
            temperature: state.temperature || 21,
            devices: state.devices || [],
          };
        }

        // Now apply the state to WASM memory so it's in sync
        if (wasm?.set_lighting && stateToApply.lighting !== 50) {
          wasm.set_lighting(1, stateToApply.lighting);
          console.log("🔧 Applied lighting to WASM:", stateToApply.lighting);
        }

        if (wasm?.set_temperature && stateToApply.temperature !== 21) {
          wasm.set_temperature(1, stateToApply.temperature);
          console.log("🔧 Applied temperature to WASM:", stateToApply.temperature);
        }

        // Restore device states
        if (wasm?.get_state_json) {
          const currentState = JSON.parse(wasm.get_state_json(1));
          const currentDevices = currentState.devices || [];

          for (const savedDevice of stateToApply.devices) {
            const currentDevice = currentDevices.find(
              (d: any) => d.id === savedDevice.id,
            );
            if (currentDevice && currentDevice.status !== savedDevice.status) {
              wasm.toggle_device(1, savedDevice.id);
              console.log(
                "🔧 Applied device state to WASM:",
                savedDevice.id,
                savedDevice.status,
              );
            }
          }
        }

        setRoomState(stateToApply);
      } catch (err) {
        console.error("❌ Failed to initialize room state:", err);
      }
    }
  }, [wasm]);

  const handleLightingChange = (value: number) => {
    if (wasm?.set_lighting) {
      wasm.set_lighting(1, value);
      setRoomState((prev) => ({ ...prev, lighting: value }));
    }
  };

  const handleTemperatureChange = (value: number) => {
    if (wasm?.set_temperature) {
      wasm.set_temperature(1, value);
      setRoomState((prev) => ({ ...prev, temperature: value }));
    }
  };

  const handleToggleDevice = (deviceId: number) => {
    if (wasm?.toggle_device) {
      wasm.toggle_device(1, deviceId);
      // Refresh state
      if (wasm.get_state_json) {
        const stateJson = wasm.get_state_json(1);
        const state = JSON.parse(stateJson);
        setRoomState({
          lighting: state.lighting,
          temperature: state.temperature,
          devices: state.devices || [],
        });
      }
    }
  };

  const totalPower = wasm?.get_total_power ? wasm.get_total_power(1) : 0;

  const handleResetState = () => {
    if (wasm?.init_room) {
      console.log("🔄 Resetting room state");
      wasm.init_room();
      // Re-fetch state from WASM
      if (wasm.get_state_json) {
        try {
          const stateJson = wasm.get_state_json(1);
          const state = JSON.parse(stateJson);
          setRoomState({
            lighting: state.lighting || 50,
            temperature: state.temperature || 21,
            devices: state.devices || [],
          });
        } catch (err) {
          console.error("Failed to reset state:", err);
        }
      }
    }
  };

  return (
    <Window x='center' y='center' width='700' height='600' maxWidth='900' maxHeight='800'>
      <DraggableHandle className='flex items-center justify-between px-2 py-5 border-b bg-gradient-to-r from-blue-600 to-purple-600 z-50 pl-20'>
        <span>Demo Room</span>
      </DraggableHandle>
      <ControlButtons />

      <div className='flex-1 overflow-auto p-6 bg-gradient-to-br from-slate-900 to-slate-800'>
        {loading && (
          <div className='flex flex-col items-center justify-center h-full gap-3'>
            <div className='text-4xl animate-spin'>⏳</div>
            <p className='text-slate-300'>Loading Demo Room...</p>
          </div>
        )}

        {error && (
          <div className='bg-red-900/30 border border-red-600 rounded p-4'>
            <p className='text-red-400 font-mono text-sm'>❌ Error</p>
            <p className='text-red-300 text-xs mt-2'>{error}</p>
          </div>
        )}

        {!loading && !error && (
          <div className='space-y-6'>
            {/* Room Header */}
            <div className='bg-slate-800 border border-slate-700 rounded-lg p-4'>
              <h2 className='text-2xl font-bold text-white mb-2'>Living Room</h2>
              <div className='flex gap-4 text-sm'>
                <span className='text-slate-400'>
                  Status: <span className='text-green-400'>Active</span>
                </span>
                <span className='text-slate-400'>
                  Power: <span className='text-yellow-400 font-mono'>{totalPower}W</span>
                </span>
              </div>
            </div>

            {/* Controls */}
            <div className='grid grid-cols-2 gap-4'>
              {/* Lighting */}
              <div className='bg-slate-800 border border-slate-700 rounded-lg p-4'>
                <label className='text-slate-300 text-sm font-mono mb-3 block'>
                  💡 Lighting: {roomState.lighting}%
                </label>
                <input
                  type='range'
                  min='0'
                  max='100'
                  value={roomState.lighting}
                  onChange={(e) => handleLightingChange(parseInt(e.target.value))}
                  className='w-full accent-blue-500'
                />
              </div>

              {/* Temperature */}
              <div className='bg-slate-800 border border-slate-700 rounded-lg p-4'>
                <label className='text-slate-300 text-sm font-mono mb-3 block'>
                  🌡️ Temperature: {roomState.temperature}°C
                </label>
                <input
                  type='range'
                  min='16'
                  max='30'
                  value={roomState.temperature}
                  onChange={(e) => handleTemperatureChange(parseInt(e.target.value))}
                  className='w-full accent-red-500'
                />
              </div>
            </div>

            {/* Devices */}
            <div className='bg-slate-800 border border-slate-700 rounded-lg p-4'>
              <h3 className='text-slate-300 font-mono text-sm mb-4'>🔌 Devices</h3>
              <div className='space-y-2'>
                {roomState.devices.length === 0 ? (
                  <p className='text-slate-500 text-sm'>No devices available</p>
                ) : (
                  roomState.devices.map((device) => (
                    <button
                      key={device.id}
                      onClick={() => handleToggleDevice(device.id)}
                      className={`w-full px-4 py-2 rounded text-sm font-mono transition text-left flex justify-between items-center ${
                        device.status
                          ? "bg-green-900/40 border border-green-600 text-green-300 hover:bg-green-900/60"
                          : "bg-slate-700/40 border border-slate-600 text-slate-400 hover:bg-slate-700/60"
                      }`}
                    >
                      <span>{device.name}</span>
                      <span>
                        {device.status ? "on" : "off"} • {device.power}W
                      </span>
                    </button>
                  ))
                )}
              </div>
            </div>

            {/* Debug Info */}
            <details className='bg-slate-800 border border-slate-700 rounded-lg p-4'>
              <summary className='text-slate-400 text-sm font-mono cursor-pointer hover:text-slate-300'>
                📊 WASM State (Debug)
              </summary>
              <pre className='mt-2 bg-slate-950 p-2 rounded text-xs text-slate-400 overflow-x-auto'>
                {JSON.stringify(roomState, null, 2)}
              </pre>
              <div className='mt-3 flex gap-2'>
                <button
                  onClick={handleResetState}
                  className='px-3 py-1 bg-orange-900/40 border border-orange-600 text-orange-300 rounded text-xs font-mono hover:bg-orange-900/60 transition'
                >
                  🔄 Reset to Defaults
                </button>
                <button
                  onClick={() => {
                    localStorage.removeItem("demo-room-state");
                    handleResetState();
                  }}
                  className='px-3 py-1 bg-red-900/40 border border-red-600 text-red-300 rounded text-xs font-mono hover:bg-red-900/60 transition'
                >
                  🗑️ Clear Storage
                </button>
              </div>
            </details>
          </div>
        )}
      </div>
    </Window>
  );
}

export default DemoRoomApp;
