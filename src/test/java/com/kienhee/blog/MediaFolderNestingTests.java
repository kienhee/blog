package com.kienhee.blog;

import com.kienhee.blog.dto.MediaFolderCreateRequest;
import com.kienhee.blog.entity.MediaFolder;
import com.kienhee.blog.repository.MediaFolderRepository;
import com.kienhee.blog.service.MediaFolderService;
import com.kienhee.blog.service.impl.MediaStorageLayout;
import com.kienhee.blog.storage.FilesystemStorage;
import com.kienhee.blog.support.MediaFolderCleanup;
import com.kienhee.blog.support.TestAuth;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Nested;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.security.test.web.servlet.setup.SecurityMockMvcConfigurers;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.setup.MockMvcBuilders;
import org.springframework.web.context.WebApplicationContext;

import java.util.ArrayList;
import java.util.Comparator;
import java.util.List;

import static org.junit.jupiter.api.Assertions.*;
import static org.springframework.security.test.web.servlet.request.SecurityMockMvcRequestPostProcessors.csrf;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

/**
 * Nested media folders: materialized path bookkeeping, cycle prevention, per-parent slug
 * scoping and breadcrumbs. Hits a real database (repo convention) and deletes every row
 * it created, deepest first — the parent FK is ON DELETE RESTRICT.
 */
@SpringBootTest
@DisplayName("Media nested folders")
class MediaFolderNestingTests {

    @Autowired
    private MediaFolderService folderService;
    @Autowired
    private MediaFolderRepository folderRepository;
    @Autowired
    private WebApplicationContext context;
    @Autowired
    private MediaStorageLayout layout;
    @Autowired
    private FilesystemStorage storage;

    private MockMvc mockMvc;
    private final List<Long> created = new ArrayList<>();
    private String tag;

    @BeforeEach
    void setUp() {
        mockMvc = MockMvcBuilders.webAppContextSetup(context)
                .apply(SecurityMockMvcConfigurers.springSecurity())
                .build();
        tag = "nest" + System.nanoTime();
    }

    @AfterEach
    void cleanUp() {
        MediaFolderCleanup.deleteFoldersAndDirectories(created, folderRepository, layout, storage);
        created.clear();
    }

    private MediaFolder create(String name, Long parentId) {
        MediaFolder folder = folderService.createFolder(MediaFolderCreateRequest.builder()
                .name(name)
                .parentId(parentId)
                .build());
        created.add(folder.getId());
        return folder;
    }

    private MediaFolder reload(Long id) {
        return folderRepository.findById(id).orElseThrow();
    }

    /**
     * Parent assertions go through the path, not {@code getParent()}: the association is
     * LAZY and open-in-view is off, so touching it outside a transaction would blow up.
     */
    private void assertParentIs(Long expectedParentId, Long childId) {
        MediaFolder child = reload(childId);
        String expected = (expectedParentId == null ? "/" : reload(expectedParentId).getPath()) + childId + "/";
        assertEquals(expected, child.getPath());
    }

    @Nested
    @DisplayName("Create")
    class CreateTests {

        @Test
        @DisplayName("three levels of nesting get the right path and depth")
        void createsThreeLevels() {
            MediaFolder a = create(tag + " A", null);
            MediaFolder b = create(tag + " B", a.getId());
            MediaFolder c = create(tag + " C", b.getId());

            assertEquals("/" + a.getId() + "/", a.getPath());
            assertEquals(0, a.getDepth());
            assertEquals("/" + a.getId() + "/" + b.getId() + "/", b.getPath());
            assertEquals(1, b.getDepth());
            assertEquals("/" + a.getId() + "/" + b.getId() + "/" + c.getId() + "/", c.getPath());
            assertEquals(2, c.getDepth());

            // And it survives a round-trip through the database, not just in memory.
            assertEquals(c.getPath(), reload(c.getId()).getPath());
            assertEquals(2, reload(c.getId()).getDepth());
        }

        @Test
        @DisplayName("same slug under the same parent is rejected, under another parent is fine")
        void slugIsScopedToTheParent() {
            MediaFolder a = create(tag + " A", null);
            MediaFolder b = create(tag + " B", null);
            create("Photos", a.getId());

            IllegalArgumentException error = assertThrows(IllegalArgumentException.class,
                    () -> create("Photos", a.getId()));
            assertTrue(error.getMessage().contains("folder_exists"), error.getMessage());

            // The very same slug in a different branch is legitimate.
            MediaFolder other = create("Photos", b.getId());
            assertEquals("photos", other.getSlug());
        }

        @Test
        @DisplayName("root-level duplicates are blocked in the service (MySQL ignores NULL in the unique index)")
        void rootDuplicatesBlocked() {
            create(tag + " Root", null);
            assertThrows(IllegalArgumentException.class, () -> create(tag + " Root", null));
        }

        @Test
        @DisplayName("nesting deeper than the cap is refused")
        void depthCapEnforced() {
            Long parentId = null;
            for (int i = 0; i <= MediaFolderService.MAX_DEPTH; i++) {
                parentId = create(tag + " L" + i, parentId).getId();
            }
            Long tooDeep = parentId;
            IllegalArgumentException error = assertThrows(IllegalArgumentException.class,
                    () -> create(tag + " overflow", tooDeep));
            assertTrue(error.getMessage().startsWith("error.media.depth"), error.getMessage());
        }
    }

    @Nested
    @DisplayName("Move")
    class MoveTests {

        @Test
        @DisplayName("moving a folder rewrites path and depth for the whole subtree")
        void movesSubtree() {
            MediaFolder a = create(tag + " A", null);
            MediaFolder b = create(tag + " B", a.getId());
            MediaFolder c = create(tag + " C", b.getId());
            MediaFolder d = create(tag + " D", null);

            folderService.moveFolder(b.getId(), d.getId());

            MediaFolder movedB = reload(b.getId());
            MediaFolder movedC = reload(c.getId());
            assertEquals("/" + d.getId() + "/" + b.getId() + "/", movedB.getPath());
            assertEquals(1, movedB.getDepth());
            // The descendant is the part that silently rots if only the node is rewritten.
            assertEquals("/" + d.getId() + "/" + b.getId() + "/" + c.getId() + "/", movedC.getPath());
            assertEquals(2, movedC.getDepth());
            assertEquals(0, folderRepository.countByParentId(a.getId()));
        }

        @Test
        @DisplayName("moving to the root resets depth to 0")
        void movesToRoot() {
            MediaFolder a = create(tag + " A", null);
            MediaFolder b = create(tag + " B", a.getId());
            MediaFolder c = create(tag + " C", b.getId());

            folderService.moveFolder(b.getId(), null);

            assertEquals("/" + b.getId() + "/", reload(b.getId()).getPath());
            assertEquals(0, reload(b.getId()).getDepth());
            assertEquals("/" + b.getId() + "/" + c.getId() + "/", reload(c.getId()).getPath());
            assertEquals(1, reload(c.getId()).getDepth());
        }

        @Test
        @DisplayName("a folder cannot be moved into itself or one of its descendants")
        void rejectsCycles() {
            MediaFolder a = create(tag + " A", null);
            MediaFolder b = create(tag + " B", a.getId());
            MediaFolder c = create(tag + " C", b.getId());

            assertThrows(IllegalArgumentException.class, () -> folderService.moveFolder(a.getId(), a.getId()));
            assertThrows(IllegalArgumentException.class, () -> folderService.moveFolder(a.getId(), b.getId()));
            assertThrows(IllegalArgumentException.class, () -> folderService.moveFolder(a.getId(), c.getId()));

            // Nothing was written by the rejected attempts.
            assertEquals("/" + a.getId() + "/", reload(a.getId()).getPath());
            assertEquals("/" + a.getId() + "/" + b.getId() + "/" + c.getId() + "/", reload(c.getId()).getPath());
        }

        @Test
        @DisplayName("moving into a parent that already has that slug is rejected")
        void rejectsSlugCollisionAtDestination() {
            MediaFolder a = create(tag + " A", null);
            MediaFolder b = create(tag + " B", null);
            MediaFolder moving = create("Reports", a.getId());
            create("Reports", b.getId());

            assertThrows(IllegalArgumentException.class,
                    () -> folderService.moveFolder(moving.getId(), b.getId()));
            assertParentIs(a.getId(), moving.getId());
        }
    }

    @Nested
    @DisplayName("Rename / delete / breadcrumb")
    class OtherTests {

        @Test
        @DisplayName("rename updates both name and slug and rejects a sibling collision")
        void renameUpdatesSlug() {
            MediaFolder a = create(tag + " A", null);
            MediaFolder one = create("Alpha One", a.getId());
            create("Beta Two", a.getId());

            MediaFolder renamed = folderService.renameFolder(one.getId(), "Gamma Three");
            assertEquals("Gamma Three", renamed.getName());
            assertEquals("gamma-three", renamed.getSlug());
            assertEquals("gamma-three", reload(one.getId()).getSlug());

            assertThrows(IllegalArgumentException.class,
                    () -> folderService.renameFolder(one.getId(), "Beta Two"));
        }

        @Test
        @DisplayName("breadcrumb lists ancestors root-first, folder last")
        void breadcrumbOrder() {
            MediaFolder a = create(tag + " A", null);
            MediaFolder b = create(tag + " B", a.getId());
            MediaFolder c = create(tag + " C", b.getId());

            List<MediaFolder> trail = folderService.getBreadcrumb(c.getId());
            assertEquals(List.of(a.getId(), b.getId(), c.getId()),
                    trail.stream().map(MediaFolder::getId).toList());
            assertTrue(folderService.getBreadcrumb(null).isEmpty());
        }

        @Test
        @DisplayName("the flattened tree keeps children directly under their parent")
        void folderTreeIsDepthFirst() {
            MediaFolder a = create(tag + " A", null);
            MediaFolder b = create(tag + " B", a.getId());

            List<MediaFolderService.FolderNode> tree = folderService.getFolderTree();
            int ai = indexOf(tree, a.getId());
            int bi = indexOf(tree, b.getId());
            assertTrue(ai >= 0 && bi == ai + 1, "child should follow its parent immediately");
            assertEquals(0, tree.get(ai).depth());
            assertEquals(1, tree.get(bi).depth());
        }

        private int indexOf(List<MediaFolderService.FolderNode> tree, Long id) {
            for (int i = 0; i < tree.size(); i++) {
                if (tree.get(i).folder().getId().equals(id)) {
                    return i;
                }
            }
            return -1;
        }

        @Test
        @DisplayName("deleting a folder with subfolders trashes the whole subtree")
        void deleteCascadesToSubfolders() {
            MediaFolder a = create(tag + " A", null);
            MediaFolder b = create(tag + " B", a.getId());
            MediaFolder c = create(tag + " C", b.getId());
            folderService.deleteFolder(a.getId());
            for (MediaFolder f : List.of(a, b, c)) {
                assertEquals(MediaFolder.Status.TRASHED, folderRepository.findById(f.getId()).orElseThrow().getStatus(),
                        f.getName() + " must follow its ancestor into the trash");
            }
        }
    }

    @Nested
    @DisplayName("Controller")
    class ControllerTests {

        @Test
        @DisplayName("POST /admin/media/folders with a parentId creates a child and returns to it")
        void createsSubfolderViaController() throws Exception {
            MediaFolder parent = create(tag + " Parent", null);

            mockMvc.perform(post("/admin/media/folders")
                            .with(TestAuth.owner())
                            .with(csrf())
                            .param("name", tag + " Child")
                            .param("parentId", String.valueOf(parent.getId())))
                    .andExpect(status().is3xxRedirection())
                    .andExpect(redirectedUrl("/admin/media?folder=" + parent.getId()));

            List<MediaFolder> children = folderRepository.findByParentIdOrderByNameAsc(parent.getId());
            assertEquals(1, children.size());
            created.add(children.get(0).getId());
            assertEquals(1, children.get(0).getDepth());
        }

        @Test
        @DisplayName("POST /admin/media/folders/{id}/move re-parents the folder")
        void movesViaController() throws Exception {
            MediaFolder a = create(tag + " A", null);
            MediaFolder b = create(tag + " B", null);
            MediaFolder child = create(tag + " Child", a.getId());

            mockMvc.perform(post("/admin/media/folders/" + child.getId() + "/move")
                            .with(TestAuth.owner())
                            .with(csrf())
                            .param("parentId", String.valueOf(b.getId())))
                    .andExpect(status().is3xxRedirection())
                    .andExpect(redirectedUrl("/admin/media?folder=" + b.getId()));

            assertParentIs(b.getId(), child.getId());
        }

        @Test
        @DisplayName("move without media:edit is forbidden")
        void moveRequiresPermission() throws Exception {
            MediaFolder a = create(tag + " A", null);
            mockMvc.perform(post("/admin/media/folders/" + a.getId() + "/move")
                            .with(TestAuth.withPermissions("viewer@test.com", "media:view"))
                            .with(csrf()))
                    .andExpect(status().isForbidden());
        }

        @Test
        @DisplayName("the media page opens the explorer on the requested nested folder")
        void pageRendersBreadcrumb() throws Exception {
            MediaFolder a = create(tag + " A", null);
            MediaFolder b = create(tag + " B", a.getId());

            mockMvc.perform(get("/admin/media").param("folder", String.valueOf(b.getId()))
                            .with(TestAuth.owner()))
                    .andExpect(status().isOk())
                    .andExpect(model().attribute("currentFolderId", b.getId()))
                    .andExpect(content().string(org.hamcrest.Matchers.containsString("data-initial-folder=\"" + b.getId() + "\"")));
        }
    }
}
