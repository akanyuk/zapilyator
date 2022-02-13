		define SNA_FILENAME "test.sna"
		define TRD_FILENAME "test.trd"
		define BIN_FILENAME "test.bin"

		device zxspectrum128

		;define _debug_border=1
		%if_anima%define _anima
		%if_anima2%define _anima2
		%if_anima3%define _anima3
		%if_anima_bg%define _anima_bg
		%if_music%define _play_music
		%if_anal%define _analyzator %analyzator_chanel%
		%if_anal_splash%define _analyzator_splash %analyzator_splash_chanel%
		%if_splash%define _splash_screen
		%if_scroll%define _scroll

		
		ifdef _play_music
		define _page1
		endif

		ifdef _anima2
		define _page4
		endif

		ifdef _anima3
		define _page6
		endif
		
		ifdef _splash_screen
		define _page3
		endif

		ifdef _anima_bg
		ifndef _page3
		define _page3
		endif
		endif

		ifdef _analyzator
		define _analyzator_sens %analyzator_sens%
		ifndef _page3
		define _page3
		endif
		endif

		ifdef _analyzator_splash
		define _analyzator_splash_sens %analyzator_splash_sens%
		ifndef _page3
		define _page3
		endif
		endif
		
		ifdef _scroll
		define _page7
		define _scroll_address %scroll_address%
		endif
		
		page 0
		
		org #6000
page0s	di	: ld sp, $-2
		ld a, %border_color% : out (#fe), a 
		
		ifdef _play_music
		; init PT3 player
		ld a,#11 : call set_page
		call PT3PLAY
		endif
		
		; init im2
		ld a,#5c, i,a, hl,interr, (#5cff),hl : im 2
		ei

		ifdef _splash_screen
		di
		ld a,#13 : call set_page
		ld hl,#c000, de,#4000, bc,#1b00 : ldir
		
		ifdef _analyzator_splash
		call createAnalSplash
		xor a : ld (SKIP_ANS + 1), a
		endif		

		ei
		
		; splash pause
		ifdef _play_music
sp_puse	ld a, #00
		ld hl, CUR_PATTERN
		cp (hl) : jr z, sp_puse
		ld a, (hl) : ld (sp_puse + 1), a
P_COUNTER	
		ld a, #00 : inc a : ld (P_COUNTER + 1), a
		cp %splash_delay% : jr c, sp_puse
		else
		ld b, #ff : halt : djnz $-1
		endif
		endif

		; stop splash analizator
		ifdef _analyzator_splash
		ld a, #ff : ld (SKIP_ANS + 1), a
		endif		
		
		; change border
		ld a, %animation_border% : out (#fe), a 
		
		; clear screen after splash
		ifdef _anima_bg
		ld a,#13 : call set_page
		ld hl,ANIMA_BG, de,#4000, bc,#1b00 : ldir
		ifdef _analyzator
		call createAnal
		xor a : ld (SKIP_AN + 1), a
		endif		
		else
		ld hl,#4000, de,#4001, bc,#1800, (hl), l : ldir
		ld hl,#5800, de,#5801, bc,#2ff, (hl),%main_color% : ldir
		endif
		
		ifdef _scroll
		xor a : ld (SKIP_SC	+ 1), a
		ld hl, %scroll_attr%
		ld d,h, e,l : inc de
		ld bc, #003f
		ld (hl), %scroll_color%
		ldir
		endif
		
		; start animation
		xor a
		ifdef _anima
		ld (ANIMA_START + 1), a
		endif
		ifdef _anima2
		ld (ANIMA2_START + 1), a
		endif
		ifdef _anima3
		ld (ANIMA3_START + 1), a
		endif
		
		jr $
		
		ifdef _anima
anima	ld a, #01 : dec a : ld (anima + 1), a : ret nz	; animation pause
anima_p	ld ix, anima_proc
		ld a, (ix + 0) : or a : jr nz, 1f
		ld ix, anima_proc + 3
1		ld a, (ix + 0) : ld (anima + 1), a
		ld l, (ix + 1)
		ld h, (ix + 2)
		inc ix : inc ix : inc ix : ld (anima_p + 2), ix
		jp (hl)
anima_proc
%main_frames% 
		db #00
		endif
		
interr	di
		push af,bc,de,hl,ix,iy
		exx : ex af, af'
		push af,bc,de,hl,ix,iy

		ifdef _debug_border : ld a, #01 : out (#fe), a : endif ; debug

		ifdef _anima2
ANIMA2_START
		ld a, #ff : or a : jr nz, $+12
		ld a,#14 : ld bc,#7ffd : out(c), a
		call anima2
		endif

		ifdef _anima3
ANIMA3_START
		ld a, #ff : or a : jr nz, $+12
		ld a,#16 : ld bc,#7ffd : out(c), a
		call anima3
		endif
		
		ifdef _anima
ANIMA_START
		ld a, #ff : or a : jr nz, $+12
		ld a,#10 : ld bc,#7ffd : out(c), a
		call anima
		endif

		ifdef _scroll
SKIP_SC	ld a, #01 : or a : jr nz, 1f
		ld a,#17 : ld bc,#7ffd : out(c), a
		call scroll16
1		
		endif
		
		ifdef _play_music
		ld a,#11 : ld bc,#7ffd : out(c), a
		call PT3PLAY + 5
		ld a, (PT3PLAY + 11) : ld (CUR_PATTERN), a
		endif

		// restore page
		ld a,(CUR_PAGE) : call set_page

		ifdef _analyzator
SKIP_AN	ld a, #01 : or a : jr nz, $+12
		ld a,#13 : ld bc,#7ffd : out(c), a
		call doAnal
		endif

		ifdef _analyzator_splash
SKIP_ANS
		ld a, #01 : or a : jr nz, $+12
		ld a,#13 : ld bc,#7ffd : out(c), a
		call doAnalSplash
		endif
		
		pop iy,ix,hl,de,bc,af
		exx : ex af, af'
		pop iy,ix,hl,de,bc,af
		ei
		ret


set_page
		ld (CUR_PAGE), a : ld bc,#7ffd : out(c), a : ret
		
CUR_PATTERN	db #00
CUR_PAGE	db #00
		
_data
		
		ifdef _anima
%includes%
		display /d, "Animation 1 len: ", $ - _data
		endif
		
page0e

		ifdef _play_music
		page 1
		org #c000
page1s		
PT3PLAY	include "sources/PTxPlay.asm"
		incbin "res/music"
page1e
		endif

		page 3
		org #c000
page3s		
		ifdef _splash_screen
		incbin "res/splash"
		endif
		ifdef _anima_bg
ANIMA_BG
		incbin "res/bg"
		ifdef _analyzator		
		include "sources/analyzator.asm"
		endif
		endif

		ifdef _analyzator_splash
		include "sources/analyzator_splash.asm"
		endif
page3e
		

		page 4
		org #c000
page4s
		ifdef _anima2
anima2	ld a, #01 : dec a : ld (anima2 + 1), a : ret nz	; animation pause
anima2_p	ld ix, anima2_proc
		ld a, (ix + 0) : or a : jr nz, 1f
		ld ix, anima2_proc + 3
1		ld a, (ix + 0) : ld (anima2 + 1), a
		ld l, (ix + 1)
		ld h, (ix + 2)
		inc ix : inc ix : inc ix : ld (anima2_p + 2), ix
		jp (hl)
anima2_proc
%main_frames2% 
		db #00
		
_data2
%includes2%
		display /d, "Animation 2 len: ", $ - _data2
		endif
page4e
		
		page 6
		org #c000
page6s
		ifdef _anima3
anima3	ld a, #01 : dec a : ld (anima3 + 1), a : ret nz	; animation pause
anima3_p	ld ix, anima3_proc
		ld a, (ix + 0) : or a : jr nz, 1f
		ld ix, anima3_proc + 3
1		ld a, (ix + 0) : ld (anima3 + 1), a
		ld l, (ix + 1)
		ld h, (ix + 2)
		inc ix : inc ix : inc ix : ld (anima3_p + 2), ix
		jp (hl)
anima3_proc
%main_frames3% 
		db #00
_data3
%includes3%
		display /d, "Animation 3 len: ", $ - _data3
		endif
page6e
		
		ifdef _scroll
		page 7
		org #db00
page7s	include "sources/scroll.asm"
page7e
		endif
		
		; --------------------------
		include "sources/builder.asm"