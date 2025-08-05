import { exec } from 'node:child_process';
import { HotUpdateOptions } from 'vite';
import { minimatch } from 'minimatch';
import path from 'path';

export function typescriptTransformerPlugin(options: { watchPaths?: string[], debounceDelay?: number, verbose?: boolean, command?: string } = {}) {
    const {
        // PHP files to watch (default: app and routes directories)
        watchPaths = ['app/**/*.php', 'routes/**/*.php'],
        // Debounce delay in milliseconds
        debounceDelay = 500,
        // Command to run for transformation
        command = 'php artisan typescript:transform --format',
        // Enable logging
        verbose = true
    } = options;

    let isTransforming = false;

    const log = (message: string) => {
        if (verbose) {
            console.log(`[TypeScript Transformer] ${message}`);
        }
    };

    const runTransformer = () => {
        return new Promise<void>((resolve, reject) => {
            exec(command, (error, stdout, stderr) => {
                if (error) {
                    console.error(`[TypeScript Transformer] Error: ${error.message}`);
                    return reject(error);
                }
                if (stderr) {
                    console.error(`[TypeScript Transformer] Stderr: ${stderr}`);
                    return resolve();
                }
                log(`Transformation output:\n${stdout}`);
                resolve();
            });
        });
    };

    const debouncedTransform = () => {
        // すでに実行中の場合はスキップ
        if (isTransforming) {
            log('Transformation in progress, request skipped');
            return;
        }
        isTransforming = true;

        setTimeout(async () => {
            try {
                await runTransformer();
                isTransforming = false;
            } catch (err) {
                const message = err instanceof Error ? err.message : String(err);
                console.error('[TypeScript Transformer] Transform failed:', message);
            }
        }, debounceDelay);
    };

    return {
        name: 'typescript-transformer',

        buildStart() {
            // Run initial transformation
            return runTransformer();
        },

        hotUpdate({ file, server }: HotUpdateOptions) {
            // Check if any of the watched files were updated
            const shouldTransform = file && watchPaths.some(pattern => {
                return minimatch(file, path.resolve(server.config.root, pattern));
            });

            if (shouldTransform) {
                const relativePath = path.relative(process.cwd(), file);
                log(`Hot update triggered by: ${relativePath}`);
                debouncedTransform();
            }
        }
    };
}
