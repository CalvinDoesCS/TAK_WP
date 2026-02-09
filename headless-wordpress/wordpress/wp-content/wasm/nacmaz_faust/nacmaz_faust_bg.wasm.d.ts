/* tslint:disable */
/* eslint-disable */
export const memory: WebAssembly.Memory;
export const alloc: (a: number) => number;
export const dealloc: (a: number, b: number) => void;
export const get_file: (a: number, b: number) => number;
export const get_file_count: () => number;
export const get_file_path_len: (a: number) => number;
export const get_file_path_ptr: (a: number) => number;
export const get_last_file_len: () => number;
export const get_last_file_mime_len: () => number;
export const get_last_file_mime_ptr: () => number;
export const get_last_file_ptr: () => number;
export const get_wp_data_len: () => number;
export const get_wp_data_ptr: () => number;
export const receive_wp_data: (a: number, b: number) => number;
export const __wbindgen_externrefs: WebAssembly.Table;
export const __wbindgen_start: () => void;
