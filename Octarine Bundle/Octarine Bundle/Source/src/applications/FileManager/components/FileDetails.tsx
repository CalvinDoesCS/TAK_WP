import { useContext } from "react";
import { fileDetailsContext } from "../context/fileDetailsContext";
import { CircleEllipsis } from "lucide-react";

function Main() {
  const { fileDetails } = useContext(fileDetailsContext);

  const imageAssets = import.meta.glob<{
    default: string;
  }>("/src/assets/images/icons/*.{jpg,jpeg,png,svg}", { eager: true });

  return (
    <>
      <div className="flex-1 h-full min-w-96">
        <div className="flex flex-col h-full max-w-xl gap-10 pt-32 pb-20 overflow-y-auto border-r scrollbar">
          {fileDetails?.file.type == "file" &&
            (fileDetails?.file.extension == "app" ? (
              <img
                src={
                  imageAssets[
                    "/src/assets/images/icons/" + fileDetails?.file.icon
                  ].default
                }
                className="w-40 h-40 mx-auto"
              />
            ) : (
              <div className="flex items-center justify-center flex-none w-40 h-40 mx-auto bg-center bg-no-repeat bg-contain bg-file">
                {fileDetails?.file.defaultOpenWithApp?.file && (
                  <img
                    src={
                      imageAssets[
                        "/src/assets/images/icons/" +
                          fileDetails?.file.defaultOpenWithApp?.file.icon
                      ].default
                    }
                    className="w-10 h-10"
                  />
                )}
              </div>
            ))}
          <div className="flex flex-col gap-5 px-4">
            <div className="flex flex-col gap-1">
              <div className="font-medium">{fileDetails?.name}</div>
              <div className="font-medium text-muted-foreground/80">
                Application - 822 bytes
              </div>
            </div>
            <div className="flex flex-col gap-1">
              <div className="font-medium">Information</div>
              <div className="flex flex-col gap-1">
                <div className="flex justify-between text-xs">
                  <div className="font-medium text-muted-foreground/80">
                    Created
                  </div>
                  <div className="font-medium">
                    Monday, 15 February 2024 at 11.33
                  </div>
                </div>
                <div className="flex justify-between text-xs">
                  <div className="font-medium text-muted-foreground/80">
                    Modified
                  </div>
                  <div className="font-medium">
                    Sunday, 09 February 2024 at 12.48
                  </div>
                </div>
                <div className="flex justify-between text-xs">
                  <div className="font-medium text-muted-foreground/80">
                    Last opened
                  </div>
                  <div className="font-medium">
                    Sunday, 09 February 2024 at 10.50
                  </div>
                </div>
              </div>
            </div>
            <div className="flex flex-col gap-1">
              <div className="font-medium">Tags</div>
              <div className="font-medium text-muted-foreground/80">
                Add Tags...
              </div>
            </div>
          </div>
          <div className="flex flex-col items-center gap-1.5 mt-auto text-muted-foreground/80">
            <CircleEllipsis className="w-4 h-4" />
            <div className="">More...</div>
          </div>
        </div>
      </div>
    </>
  );
}

export default Main;
