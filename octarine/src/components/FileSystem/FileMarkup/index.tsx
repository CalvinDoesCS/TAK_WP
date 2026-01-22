import React, { useEffect } from "react";
import { cn } from "@/lib/utils";
import { type File } from "../FileUtils/types";
import { getFormattedFileName } from "./fileMarkupUtils";
import FileInput from "../FileInput";
import { useFilesStore } from "@/stores/filesStore";

export interface MainProps {
  type?: "thumbnail" | "list";
  fileName: string;
  file: File;
  path: string;
  displayInput?: boolean;
}

const imageAssets = import.meta.glob<{
  default: string;
}>("/src/assets/images/icons/*.{jpg,jpeg,png,svg}", { eager: true });

const Main = React.forwardRef<HTMLDivElement, MainProps>(
  ({ fileName, file, type = "thumbnail", path, displayInput = false }, ref) => {
    const { selectFile, updateFileProperties, Root } = useFilesStore();
    const formattedFileName = getFormattedFileName(fileName);

    useEffect(() => {
      if (file.selected) {
        const handleKeyDown = (event: globalThis.KeyboardEvent) => {
          // Edit edit mode
          if (
            event.key === "Enter" &&
            !file.editable &&
            Object.entries(selectFile(path).entries).filter(
              ([fileName, file]) => file.selected
            ).length === 1 &&
            displayInput
          ) {
            updateFileProperties({
              path: path,
              properties: {
                editable: true,
              },
              name: fileName,
            });
          }

          // Cancel edit mode
          if (event.key === "Escape" && file.editable) {
            updateFileProperties({
              path: path,
              properties: {
                editable: false,
              },
              name: fileName,
            });
          }
        };

        window.addEventListener("keydown", handleKeyDown);

        return () => {
          window.removeEventListener("keydown", handleKeyDown);
        };
      }
    }, [
      updateFileProperties,
      file.selected,
      file.editable,
      selectFile,
      Root,
      file,
      path,
      displayInput,
    ]);

    return type == "thumbnail" ? (
      <div
        ref={ref}
        className="w-[5.4rem] h-[5.4rem] flex flex-col items-center gap-1 group"
      >
        <div
          className={cn([
            "p-0.5 rounded-md",
            { "group-[.focus-state]:bg-white/20": file.selected },
          ])}
        >
          {file.type == "directory" &&
            (Object.entries(file.entries).length ? (
              <div className="flex-none w-10 h-10 bg-center bg-no-repeat bg-contain bg-directory drop-shadow-[0_4px_3px_rgb(0_0_0_/_30%)]"></div>
            ) : (
              <div className="flex-none w-10 h-10 bg-center bg-no-repeat bg-contain bg-empty-directory drop-shadow-[0_4px_3px_rgb(0_0_0_/_30%)]"></div>
            ))}
          {file.type == "file" &&
            (file.extension == "app" ? (
              <img
                src={
                  imageAssets["/src/assets/images/icons/" + file.icon].default
                }
                className="w-11 h-11 drop-shadow-[0_4px_3px_rgb(0_0_0_/_30%)]"
              />
            ) : (
              <div className="flex items-center justify-center flex-none w-10 h-10 bg-center bg-no-repeat bg-contain bg-file">
                {file.defaultOpenWithApp?.file && (
                  <img
                    src={
                      imageAssets[
                        "/src/assets/images/icons/" +
                          file.defaultOpenWithApp?.file.icon
                      ].default
                    }
                    className="w-5 h-5"
                  />
                )}
              </div>
            ))}
        </div>
        <div className="leading-[.8] text-center">
          {file.editable && displayInput ? (
            <FileInput
              type={type}
              fileName={fileName}
              file={file}
              path={path}
            />
          ) : (
            <span
              className={cn([
                "text-xs text-white box-decoration-clone [text-shadow:_0px_2px_7px_rgb(0_0_0)] px-1.5 py-0.5 rounded",
                { "group-[.focus-state]:bg-blue-600/80": file.selected },
                { "group-[.focus-state]:bg-transparent": !file.selected },
              ])}
            >
              {formattedFileName.firstLine}
              <br />
              {formattedFileName.secondLine}
            </span>
          )}
        </div>
      </div>
    ) : (
      <div
        ref={ref}
        className={cn([
          "py-1 px-2 rounded flex items-center hover:bg-muted",
          "group-[.focus-state]:[&.selected]:bg-blue-600/80 hover:group-[.focus-state]:[&.selected]:bg-blue-600/80",
          { selected: file.selected },
        ])}
      >
        <div className="flex justify-center w-5">
          {file.type == "directory" &&
            (Object.entries(file.entries).length ? (
              <div className="flex-none w-5 h-5 bg-center bg-no-repeat bg-contain bg-directory"></div>
            ) : (
              <div className="flex-none w-5 h-5 bg-center bg-no-repeat bg-contain bg-empty-directory"></div>
            ))}
          {file.type == "file" &&
            (file.extension == "app" ? (
              <img
                src={
                  imageAssets["/src/assets/images/icons/" + file.icon].default
                }
                className="w-5 h-5"
              />
            ) : (
              <div className="flex items-center justify-center flex-none w-[1.15rem] h-[1.15rem] bg-center bg-no-repeat bg-contain bg-file">
                {file.defaultOpenWithApp?.file && (
                  <img
                    src={
                      imageAssets[
                        "/src/assets/images/icons/" +
                          file.defaultOpenWithApp?.file.icon
                      ].default
                    }
                    className="w-2.5 h-2.5"
                  />
                )}
              </div>
            ))}
        </div>
        {file.editable && displayInput ? (
          <FileInput type={type} fileName={fileName} file={file} path={path} />
        ) : (
          <div
            className={cn([
              "ml-2",
              { "group-[.focus-state]:text-white": file.selected },
            ])}
          >
            {formattedFileName.firstLine + formattedFileName.secondLine}
          </div>
        )}
      </div>
    );
  }
);

export default Main;
