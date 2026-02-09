/* tslint:disable */
/* eslint-disable */

export function alloc(len: number): number;

export function dealloc(ptr: number, len: number): void;

export function get_file(path_ptr: number, path_len: number): number;

export function get_file_count(): number;

export function get_file_path_len(index: number): number;

export function get_file_path_ptr(index: number): number;

export function get_last_file_len(): number;

export function get_last_file_mime_len(): number;

export function get_last_file_mime_ptr(): number;

export function get_last_file_ptr(): number;

export function get_wp_data_len(): number;

export function get_wp_data_ptr(): number;

export function receive_wp_data(ptr: number, len: number): number;

export type InitInput = RequestInfo | URL | Response | BufferSource | WebAssembly.Module;

export interface InitOutput {
    readonly memory: WebAssembly.Memory;
    readonly alloc: (a: number) => number;
    readonly dealloc: (a: number, b: number) => void;
    readonly get_file: (a: number, b: number) => number;
    readonly get_file_count: () => number;
    readonly get_file_path_len: (a: number) => number;
    readonly get_file_path_ptr: (a: number) => number;
    readonly get_last_file_len: () => number;
    readonly get_last_file_mime_len: () => number;
    readonly get_last_file_mime_ptr: () => number;
    readonly get_last_file_ptr: () => number;
    readonly get_wp_data_len: () => number;
    readonly get_wp_data_ptr: () => number;
    readonly receive_wp_data: (a: number, b: number) => number;
    readonly __wbindgen_externrefs: WebAssembly.Table;
    readonly __wbindgen_start: () => void;
}

export type SyncInitInput = BufferSource | WebAssembly.Module;

/**
 * Instantiates the given `module`, which can either be bytes or
 * a precompiled `WebAssembly.Module`.
 *
 * @param {{ module: SyncInitInput }} module - Passing `SyncInitInput` directly is deprecated.
 *
 * @returns {InitOutput}
 */
export function initSync(module: { module: SyncInitInput } | SyncInitInput): InitOutput;

/**
 * If `module_or_path` is {RequestInfo} or {URL}, makes a request and
 * for everything else, calls `WebAssembly.instantiate` directly.
 *
 * @param {{ module_or_path: InitInput | Promise<InitInput> }} module_or_path - Passing `InitInput` directly is deprecated.
 *
 * @returns {Promise<InitOutput>}
 */
export default function __wbg_init (module_or_path?: { module_or_path: InitInput | Promise<InitInput> } | InitInput | Promise<InitInput>): Promise<InitOutput>;
