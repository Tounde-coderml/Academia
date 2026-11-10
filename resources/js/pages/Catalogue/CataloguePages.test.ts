import { mount } from "@vue/test-utils";
import { beforeEach, describe, expect, it, vi } from "vitest";
import CatalogueIndex from "./CatalogueIndex.vue";
import EpreuveShow from "./EpreuveShow.vue";
import FiliereShow from "./FiliereShow.vue";

const mocks = vi.hoisted(() => ({
    usePage: vi.fn(),
    routerPut: vi.fn(),
    routerDelete: vi.fn(),
}));

vi.mock("@inertiajs/vue3", () => ({
    Head: { template: "<span />" },
    Link: {
        props: ["href", "ariaCurrent"],
        template: '<a :href="href"><slot /></a>',
    },
    usePage: mocks.usePage,
    router: { put: mocks.routerPut, delete: mocks.routerDelete },
}));

const defaultProps = {
    title: "Catalogue des épreuves",
    action: "/epreuves",
    epreuves: {
        data: [
            {
                id: 12,
                title: "Examen d’algèbre",
                filiere: "Informatique",
                filiere_id: 1,
                niveau: "L1",
                matiere: "Algèbre",
                matiere_id: 7,
                academic_year: "2026-2027",
                semester: "S1",
                type: "controle_continu",
                published_at: "2026-11-01 10:00:00",
                is_favorite: false,
            },
        ],
        links: [
            { url: null, label: "Previous", active: false },
            { url: "/epreuves?page=1", label: "1", active: true },
            { url: "/epreuves?page=2", label: "2", active: false },
            { url: "/epreuves?page=2", label: "Next", active: false },
        ],
    },
    filters: {
        q: "",
        filiere_id: "",
        niveau_id: "",
        matiere_id: "",
        semester: "",
        academic_year: "",
        type: "",
        sort: "recent",
    },
    resultCount: 1,
    options: {
        filieres: [{ id: 1, name: "Informatique" }],
        niveaux: [{ id: 2, name: "L1" }],
        matieres: [{ id: 7, name: "Algèbre" }],
        years: ["2026-2027"],
        semesters: [{ id: 1, name: "S1" }],
        types: [{ id: "examen", name: "Examen" }],
    },
};

describe("catalogue pages", () => {
    beforeEach(() => {
        mocks.usePage.mockReturnValue({ props: { errors: {} } });
        mocks.routerPut.mockClear();
        mocks.routerDelete.mockClear();
    });

    it("renders search filters and a published result with pedagogical links", async () => {
        const wrapper = mount(CatalogueIndex, { props: defaultProps });

        expect(wrapper.get("form").attributes("method")).toBe("get");
        expect(wrapper.get("form").attributes("action")).toBe("/epreuves");
        expect(wrapper.findAll("select")).toHaveLength(7);
        expect(wrapper.text()).toContain("1 résultat");
        expect(wrapper.text()).toContain("Examen d’algèbre");
        expect(wrapper.get('a[href="/epreuves/12"]').text()).toContain(
            "Consulter l’épreuve",
        );
        expect(wrapper.find('a[href="/matieres/7/epreuves"]').exists()).toBe(
            true,
        );
        expect(wrapper.find('a[href="/filieres/1/epreuves"]').exists()).toBe(
            true,
        );
        expect(
            wrapper.find('[aria-label="Ajouter aux favoris"]').exists(),
        ).toBe(true);
        await wrapper
            .get('[aria-label="Ajouter aux favoris"]')
            .trigger("click");
        expect(mocks.routerPut).toHaveBeenCalledWith(
            "/epreuves/12/favori",
            {},
            { preserveScroll: true },
        );
    });

    it("explains when no published school year is available", () => {
        const wrapper = mount(CatalogueIndex, {
            props: {
                ...defaultProps,
                epreuves: { ...defaultProps.epreuves, data: [] },
                resultCount: 0,
                options: { ...defaultProps.options, years: [] },
            },
        });

        expect(wrapper.text()).toContain("Aucune année disponible");
        expect(wrapper.text()).toContain(
            "Aucune épreuve publiée pour le moment",
        );
        expect(wrapper.text()).toContain(
            "Les épreuves apparaîtront ici après leur validation et leur publication.",
        );
    });

    it("shows an empty state and reset action when no result matches", () => {
        const wrapper = mount(CatalogueIndex, {
            props: {
                ...defaultProps,
                epreuves: { ...defaultProps.epreuves, data: [] },
                resultCount: 0,
            },
        });

        expect(wrapper.text()).toContain("Aucune épreuve trouvée");
        expect(wrapper.get('a[href="/epreuves"]').text()).toContain(
            "Réinitialiser",
        );
    });

    it("announces invalid search criteria to the user", () => {
        mocks.usePage.mockReturnValue({
            props: {
                errors: { semester: "Le semestre sélectionné est invalide." },
            },
        });

        const wrapper = mount(CatalogueIndex, { props: defaultProps });

        expect(wrapper.get('[role="alert"]').text()).toContain(
            "Le semestre sélectionné est invalide.",
        );
    });

    it("lists active subjects on the filiere page", () => {
        const wrapper = mount(FiliereShow, {
            props: {
                filiere: { id: 1, code: "GI", name: "Informatique" },
                matieres: [{ id: 7, name: "Algèbre" }],
            },
        });

        expect(wrapper.text()).toContain("Filière GI");
        expect(wrapper.get('a[href="/matieres/7/epreuves"]').text()).toContain(
            "Algèbre",
        );
    });

    it("shows the controlled preview only for supported documents", () => {
        const wrapper = mount(EpreuveShow, {
            props: {
                epreuve: {
                    id: 12,
                    title: "Examen d’algèbre",
                    filiere: "Informatique",
                    filiere_id: 1,
                    niveau: "L1",
                    matiere: "Algèbre",
                    matiere_id: 7,
                    academic_year: "2026-2027",
                    semester: "S1",
                    type: "examen",
                    published_at: "2026-11-01 10:00:00",
                    file_size: 1024,
                    can_preview: true,
                    is_favorite: false,
                },
            },
        });

        expect(wrapper.get("iframe").attributes("src")).toBe(
            "/epreuves/12/fichier?preview=1",
        );
        expect(
            wrapper.find('[aria-label="Ajouter aux favoris"]').exists(),
        ).toBe(true);
        expect(wrapper.get('a[href="/epreuves/12/fichier"]').text()).toContain(
            "Télécharger le fichier",
        );
    });
});
