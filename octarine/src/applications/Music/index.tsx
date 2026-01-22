import { Window, ControlButtons } from "@/components/Window";
import { PlusCircle } from "lucide-react";
import { Button } from "@/components/Base/Button";
import { ScrollArea, ScrollBar } from "@/components/Base/ScrollArea";
import { Separator } from "@/components/Base/Separator";
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from "@/components/Base/Tabs";
import { AlbumArtwork } from "./components/AlbumArtwork";
import { Menu } from "./components/Menu";
import { PodcastEmptyPlaceholder } from "./components/PodcastEmptyPlaceholder";
import { Sidebar } from "./components/Sidebar";
import { listenNowAlbums, madeForYouAlbums } from "./data/albums";
import { playlists } from "./data/playlists";

function Main() {
  return (
    <>
      <Window
        x="center"
        y="center"
        width="100%"
        height="100%"
        maxWidth="1020"
        maxHeight="90%"
      >
        <ControlButtons className="items-center -mt-0.5 ml-5 h-11" />
        <div className="flex-col hidden h-full md:flex">
          <Menu />
          <div className="grid flex-1 max-h-full border-t lg:grid-cols-6 bg-background">
            <Sidebar playlists={playlists} className="hidden lg:block" />
            <div className="max-h-full col-span-3 overflow-y-auto mb-28 scrollbar lg:col-span-5 lg:border-l">
              <div className="h-full px-4 py-6 lg:px-8">
                <Tabs defaultValue="music" className="h-full space-y-6">
                  <div className="flex items-center space-between">
                    <TabsList>
                      <TabsTrigger value="music" className="relative">
                        Music
                      </TabsTrigger>
                      <TabsTrigger value="podcasts">Podcasts</TabsTrigger>
                      <TabsTrigger value="live" disabled>
                        Live
                      </TabsTrigger>
                    </TabsList>
                    <div className="ml-auto mr-4">
                      <Button>
                        <PlusCircle />
                        Add music
                      </Button>
                    </div>
                  </div>
                  <TabsContent
                    value="music"
                    className="p-0 border-none outline-none"
                  >
                    <div className="flex items-center justify-between">
                      <div className="space-y-1">
                        <h2 className="text-2xl font-semibold tracking-tight">
                          Listen Now
                        </h2>
                        <p className="text-sm text-muted-foreground">
                          Top picks for you. Updated daily.
                        </p>
                      </div>
                    </div>
                    <Separator className="my-4" />
                    <div className="relative">
                      <ScrollArea>
                        <div className="flex pb-4 space-x-4">
                          {listenNowAlbums.map((album) => (
                            <AlbumArtwork
                              key={album.name}
                              album={album}
                              className="w-[250px]"
                              aspectRatio="portrait"
                              width={250}
                              height={330}
                            />
                          ))}
                        </div>
                        <ScrollBar orientation="horizontal" />
                      </ScrollArea>
                    </div>
                    <div className="mt-6 space-y-1">
                      <h2 className="text-2xl font-semibold tracking-tight">
                        Made for You
                      </h2>
                      <p className="text-sm text-muted-foreground">
                        Your personal playlists. Updated daily.
                      </p>
                    </div>
                    <Separator className="my-4" />
                    <div className="relative">
                      <ScrollArea>
                        <div className="flex pb-4 space-x-4">
                          {madeForYouAlbums.map((album) => (
                            <AlbumArtwork
                              key={album.name}
                              album={album}
                              className="w-[150px]"
                              aspectRatio="square"
                              width={150}
                              height={150}
                            />
                          ))}
                        </div>
                        <ScrollBar orientation="horizontal" />
                      </ScrollArea>
                    </div>
                  </TabsContent>
                  <TabsContent
                    value="podcasts"
                    className="h-full flex-col border-none p-0 data-[state=active]:flex"
                  >
                    <div className="flex items-center justify-between">
                      <div className="space-y-1">
                        <h2 className="text-2xl font-semibold tracking-tight">
                          New Episodes
                        </h2>
                        <p className="text-sm text-muted-foreground">
                          Your favorite podcasts. Updated daily.
                        </p>
                      </div>
                    </div>
                    <Separator className="my-4" />
                    <PodcastEmptyPlaceholder />
                  </TabsContent>
                </Tabs>
              </div>
            </div>
          </div>
        </div>
      </Window>
    </>
  );
}

export default Main;
